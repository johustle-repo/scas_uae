<?php

namespace App\Support;

use App\Models\Dog;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exports every dog record (including archived dogs) and their related
 * identification, intake, medical, treatment and placement data as an
 * Excel workbook or a PDF report.
 */
class DogDataExport
{
    /**
     * Attributes left out of every table because they are internal.
     *
     * @var list<string>
     */
    protected const EXCLUDED_ATTRIBUTES = ['dog_id', 'photo_path', 'legacy_source_row', 'archived_at'];

    /**
     * Related one-to-many records, keyed by relation name, with their table titles.
     *
     * @var array<string, string>
     */
    protected const RELATED_TABLES = [
        'vaccinations' => 'Treatments & Vaccinations',
        'fosterRecords' => 'Foster Records',
        'adoptionRecords' => 'Adoption Records',
    ];

    /**
     * Write an Excel workbook to the given file path, or "php://output" to stream it.
     */
    public function saveExcel(string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $spreadsheet->setValueBinder((new StringValueBinder)->setSetIgnoredErrors(true));

        foreach ($this->tables() as $title => $table) {
            $this->addSheet($spreadsheet, $title, $table['headings'], $table['rows']);
        }

        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * Render a PDF report with one profile section per dog.
     */
    public function pdfOutput(): string
    {
        return Pdf::loadView('exports.dogs', [
            'profiles' => $this->profiles(),
            'generatedAt' => now(),
        ])->setPaper('a4')->output();
    }

    /**
     * One profile per dog: its non-empty details, plus its related records
     * grouped by table title (without the SCAS ID / dog name columns).
     *
     * @return list<array{details: array<string, mixed>, related: array<string, array{headings: list<string>, rows: list<list<mixed>>}>}>
     */
    public function profiles(): array
    {
        $tables = $this->tables();
        $dogTable = $tables['Dogs'];
        $scasIdColumn = array_search('SCAS ID', $dogTable['headings'], true);

        return array_map(function (array $row) use ($dogTable, $scasIdColumn, $tables): array {
            $related = [];

            foreach (self::RELATED_TABLES as $title) {
                $relatedRows = array_values(array_filter(
                    $tables[$title]['rows'],
                    fn (array $relatedRow): bool => $relatedRow[0] === $row[$scasIdColumn],
                ));

                if ($relatedRows !== []) {
                    $related[$title] = [
                        'headings' => array_slice($tables[$title]['headings'], 2),
                        'rows' => array_map(fn (array $relatedRow): array => array_slice($relatedRow, 2), $relatedRows),
                    ];
                }
            }

            return [
                'details' => array_filter(
                    array_combine($dogTable['headings'], $row),
                    fn (mixed $value): bool => $value !== null && $value !== '',
                ),
                'related' => $related,
            ];
        }, $dogTable['rows']);
    }

    /**
     * Every exported table: a "Dogs" table with one row per dog (one-to-one
     * records merged in), then one table per related record type.
     *
     * @return array<string, array{headings: list<string>, rows: list<list<mixed>>}>
     */
    public function tables(): array
    {
        $dogs = Dog::query()
            ->with(['identification', 'rescueIntake', 'medicalProfile', ...array_keys(self::RELATED_TABLES)])
            ->orderBy('scas_id')
            ->get();

        $tables = ['Dogs' => $this->dogsTable($dogs)];

        foreach (self::RELATED_TABLES as $relation => $title) {
            $tables[$title] = $this->relatedTable($dogs, $relation);
        }

        return $tables;
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return array{headings: list<string>, rows: list<list<mixed>>}
     */
    protected function dogsTable(Collection $dogs): array
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

        $rows = $dogs->map(function (Dog $dog) use ($columns): array {
            $row = array_map(function (array $column) use ($dog): mixed {
                [$relation, $attribute] = $column;
                $record = $relation === '' ? $dog : $dog->getRelation($relation);

                return $record instanceof Model ? $this->formatValue($record->getAttribute($attribute)) : null;
            }, $columns);

            $row[] = $dog->archived_at === null ? 'No' : 'Yes';

            return $row;
        })->all();

        return [
            'headings' => [...array_map(fn (array $column): string => $this->heading($column[1]), $columns), 'Archived'],
            'rows' => $rows,
        ];
    }

    /**
     * One row per related record, prefixed with the dog it belongs to.
     *
     * @param  Collection<int, Dog>  $dogs
     * @return array{headings: list<string>, rows: list<list<mixed>>}
     */
    protected function relatedTable(Collection $dogs, string $relation): array
    {
        /** @var Model $related */
        $related = (new Dog)->{$relation}()->getRelated();
        $attributes = array_values(array_diff($related->getFillable(), self::EXCLUDED_ATTRIBUTES));

        $rows = $dogs->flatMap(fn (Dog $dog): array => $dog->getRelation($relation)
            ->map(fn (Model $record): array => [
                $dog->scas_id,
                $dog->name,
                ...array_map(fn (string $attribute): mixed => $this->formatValue($record->getAttribute($attribute)), $attributes),
            ])
            ->all()
        )->all();

        return [
            'headings' => ['SCAS ID', 'Dog Name', ...array_map($this->heading(...), $attributes)],
            'rows' => $rows,
        ];
    }

    /**
     * Add a worksheet with a bold, frozen heading row.
     *
     * @param  list<string>  $headings
     * @param  list<list<mixed>>  $rows
     */
    protected function addSheet(Spreadsheet $spreadsheet, string $title, array $headings, array $rows): void
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
     * Turn enums, dates and booleans into readable values.
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
