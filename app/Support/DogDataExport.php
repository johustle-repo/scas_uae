<?php

namespace App\Support;

use App\Models\Dog;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds an Excel workbook containing every dog record (including archived dogs)
 * and their related identification, intake, medical, treatment and placement data.
 */
class DogDataExport
{
    /**
     * Attributes left out of every sheet because they are internal.
     *
     * @var list<string>
     */
    protected const EXCLUDED_ATTRIBUTES = ['dog_id', 'photo_path', 'legacy_source_row', 'archived_at'];

    /**
     * Write the workbook to the given file path, or "php://output" to stream it.
     */
    public function save(string $path): void
    {
        $dogs = Dog::query()
            ->with(['identification', 'rescueIntake', 'medicalProfile', 'vaccinations', 'fosterRecords', 'adoptionRecords'])
            ->orderBy('scas_id')
            ->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $spreadsheet->setValueBinder((new StringValueBinder)->setSetIgnoredErrors(true));

        $this->addDogsSheet($spreadsheet, $dogs);
        $this->addRelatedSheet($spreadsheet, 'Treatments & Vaccinations', $dogs, 'vaccinations');
        $this->addRelatedSheet($spreadsheet, 'Foster Records', $dogs, 'fosterRecords');
        $this->addRelatedSheet($spreadsheet, 'Adoption Records', $dogs, 'adoptionRecords');

        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * One row per dog, with its one-to-one records merged in.
     *
     * @param  Collection<int, Dog>  $dogs
     */
    protected function addDogsSheet(Spreadsheet $spreadsheet, Collection $dogs): void
    {
        $dog = new Dog;
        $sections = [
            '' => $dog->getFillable(),
            'identification' => $dog->identification()->getRelated()->getFillable(),
            'rescueIntake' => $dog->rescueIntake()->getRelated()->getFillable(),
            'medicalProfile' => $dog->medicalProfile()->getRelated()->getFillable(),
        ];

        $columns = [];

        foreach ($sections as $relation => $attributes) {
            foreach (array_diff($attributes, self::EXCLUDED_ATTRIBUTES) as $attribute) {
                $columns[] = [$relation, $attribute];
            }
        }

        $headings = [...array_map(fn (array $column): string => $this->heading($column[1]), $columns), 'Archived'];

        $rows = $dogs->map(function (Dog $dog) use ($columns): array {
            $row = array_map(function (array $column) use ($dog): mixed {
                [$relation, $attribute] = $column;
                $record = $relation === '' ? $dog : $dog->getRelation($relation);

                return $record instanceof Model ? $this->formatValue($record->getAttribute($attribute)) : null;
            }, $columns);

            $row[] = $dog->archived_at === null ? 'No' : 'Yes';

            return $row;
        })->all();

        $this->writeSheet($spreadsheet, 'Dogs', $headings, $rows);
    }

    /**
     * One row per related record, prefixed with the dog it belongs to.
     *
     * @param  Collection<int, Dog>  $dogs
     */
    protected function addRelatedSheet(Spreadsheet $spreadsheet, string $title, Collection $dogs, string $relation): void
    {
        /** @var Model $related */
        $related = (new Dog)->{$relation}()->getRelated();
        $attributes = array_values(array_diff($related->getFillable(), self::EXCLUDED_ATTRIBUTES));

        $headings = ['SCAS ID', 'Dog Name', ...array_map($this->heading(...), $attributes)];

        $rows = $dogs->flatMap(fn (Dog $dog): array => $dog->getRelation($relation)
            ->map(fn (Model $record): array => [
                $dog->scas_id,
                $dog->name,
                ...array_map(fn (string $attribute): mixed => $this->formatValue($record->getAttribute($attribute)), $attributes),
            ])
            ->all()
        )->all();

        $this->writeSheet($spreadsheet, $title, $headings, $rows);
    }

    /**
     * Add a worksheet with a bold, frozen heading row.
     *
     * @param  list<string>  $headings
     * @param  list<list<mixed>>  $rows
     */
    protected function writeSheet(Spreadsheet $spreadsheet, string $title, array $headings, array $rows): void
    {
        $sheet = new Worksheet($spreadsheet, $title);
        $spreadsheet->addSheet($sheet);

        $sheet->fromArray([$headings, ...$rows], null, 'A1', true);
        $sheet->getStyle([1, 1, count($headings), 1])->getFont()->setBold(true);
        $sheet->freezePane('A2');

        foreach (range(1, count($headings)) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }
    }

    /**
     * Human column heading for an attribute name.
     */
    protected function heading(string $attribute): string
    {
        return match ($attribute) {
            'scas_id' => 'SCAS ID',
            'pcc_number' => 'PCC Number',
            'pcc_registration_date' => 'PCC Registration Date',
            'pcc_registration_status' => 'PCC Registration Status',
            default => Str::headline($attribute),
        };
    }

    /**
     * Turn enums, dates and booleans into readable cell values.
     */
    protected function formatValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => method_exists($value, 'label') ? $value->label() : $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_bool($value) => $value ? 'Yes' : 'No',
            default => $value,
        };
    }
}
