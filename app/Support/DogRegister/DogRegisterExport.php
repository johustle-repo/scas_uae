<?php

namespace App\Support\DogRegister;

use App\Enums\EnergyLevel;
use App\Models\Dog;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exports active dogs as a "Dog Register" workbook in the same layout as the
 * client's register file (columns, styling, dropdowns, formulas and
 * highlighting), so an export can be edited and imported straight back.
 */
final class DogRegisterExport
{
    private const SHEET_NAME = 'Dog Register';

    /** Empty, ready-to-fill rows added below the dogs, as in the client's file. */
    private const SPARE_ROWS = 100;

    private const HEADER_FILL = 'FF283B53';

    private const STRIPE_FILL = 'FFD8D8D8';

    /**
     * Register columns in order: header => [width, kind].
     *
     * @var array<string, array{0: int, 1: string}>
     */
    private const COLUMNS = [
        'Dog ID' => [15, 'id'],
        'Dog name' => [22, 'text'],
        'Publish to bot' => [18, 'text'],
        'Adoption status' => [13, 'text'],
        'Foster status' => [13, 'text'],
        'Sex' => [13, 'text'],
        'Breed or type' => [26, 'text'],
        'Date of birth' => [18, 'date'],
        'Birth date basis' => [13, 'text'],
        'Age (months)' => [13, 'formula'],
        'Current size' => [13, 'text'],
        'Expected adult size' => [13, 'text'],
        'Weight (kg)' => [13, 'weight'],
        'Date weighed' => [13, 'date'],
        'Energy level' => [13, 'text'],
        'Personality' => [38, 'text'],
        'Children assessment' => [23, 'text'],
        'Children observations' => [38, 'text'],
        'Dogs assessment' => [23, 'text'],
        'Dogs observations' => [38, 'text'],
        'Cats assessment' => [23, 'text'],
        'Cats observations' => [38, 'text'],
        'Home requirements' => [13, 'text'],
        'Owner experience' => [23, 'text'],
        'Alone time observations' => [38, 'text'],
        'House training' => [18, 'text'],
        'Lead walking' => [13, 'text'],
        'Basic commands' => [38, 'text'],
        'Vaccination status' => [23, 'text'],
        'Neutering status' => [13, 'text'],
        'Microchip status' => [13, 'text'],
        'Approved care summary' => [38, 'text'],
        'Approved feeding guidance' => [13, 'text'],
        'Known background' => [13, 'text'],
        'Photo URL' => [13, 'text'],
        'Profile URL' => [13, 'text'],
        'Last reviewed' => [18, 'date'],
        'Reviewed by' => [13, 'text'],
        'Bot sequence (calculated)' => [23, 'formula'],
    ];

    /**
     * Dropdown choices, keyed by the columns they apply to.
     *
     * @var array<string, list<string>>
     */
    private const DROPDOWNS = [
        'Publish to bot' => ['No', 'Yes'],
        'Adoption status' => ['Available', 'Reserved', 'Adopted', 'Not ready', 'Staff review'],
        'Foster status' => ['Not needed', 'Foster needed', 'In foster', 'Staff review'],
        'Sex' => ['Male', 'Female', 'Unknown'],
        'Birth date basis' => ['Exact', 'Estimated', 'Unknown'],
        'Current size' => ['Small', 'Medium', 'Large', 'Giant', 'Unknown'],
        'Expected adult size' => ['Small', 'Medium', 'Large', 'Giant', 'Unknown'],
        'Energy level' => ['Low', 'Moderate', 'High', 'Not assessed'],
        'Children assessment' => ['Positive observations', 'Staff review', 'Not suitable', 'Not assessed'],
        'Dogs assessment' => ['Positive observations', 'Staff review', 'Not suitable', 'Not assessed'],
        'Cats assessment' => ['Positive observations', 'Staff review', 'Not suitable', 'Not assessed'],
        'Owner experience' => ['First-time owner considered', 'Experienced owner', 'Staff review', 'Not assessed'],
        'House training' => ['Yes', 'Partly', 'No', 'Not assessed'],
        'Lead walking' => ['Trained', 'In progress', 'Staff review', 'Not assessed'],
        'Vaccination status' => ['Up to date', 'Due', 'In progress', 'Unknown'],
        'Neutering status' => ['Yes', 'No', 'Scheduled', 'Unknown'],
        'Microchip status' => ['Yes', 'No', 'Unknown'],
    ];

    /**
     * Write the register to the given path, or "php://output" to stream it.
     */
    public function save(string $path): void
    {
        $dogs = Dog::query()
            ->active()
            ->with(['identification', 'medicalProfile', 'rescueIntake', 'vaccinations'])
            ->orderBy('scas_id')
            ->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET_NAME);

        $lastRow = 1 + $dogs->count() + self::SPARE_ROWS;

        $this->writeHeader($sheet);

        foreach ($dogs->values() as $index => $dog) {
            $this->writeDog($sheet, $index + 2, $dog);
        }

        $this->writeFormulas($sheet, $lastRow);
        $this->styleRows($sheet, $lastRow);
        $this->addDropdowns($sheet, $lastRow);
        $this->addHighlighting($sheet, $lastRow);

        $sheet->freezePane('C2');
        $sheet->setAutoFilter('A1:'.$this->column('Bot sequence (calculated)').$lastRow);
        $sheet->setSelectedCell('A2');

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    private function writeHeader(Worksheet $sheet): void
    {
        $column = 1;

        foreach (self::COLUMNS as $header => [$width]) {
            $sheet->setCellValue([$column, 1], $header);
            $sheet->getColumnDimensionByColumn($column)->setWidth($width);
            $column++;
        }

        $sheet->getStyle([1, 1, count(self::COLUMNS), 1])->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_FILL]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(54.75);
    }

    private function writeDog(Worksheet $sheet, int $row, Dog $dog): void
    {
        foreach ($this->values($dog) as $header => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $kind = self::COLUMNS[$header][1];
            $coordinate = $this->column($header).$row;

            if ($value instanceof DateTimeInterface) {
                $sheet->setCellValue($coordinate, ExcelDate::PHPToExcel($value));
            } elseif ($kind === 'weight') {
                $sheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
            } else {
                // Explicit strings, so text that looks like a formula is never run.
                $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            }
        }
    }

    /**
     * One dog's register values. Register wording is used as stored; where a
     * dog was never imported, the matching profile fields fill in, using the
     * same wording the importer understands.
     *
     * @return array<string, mixed>
     */
    private function values(Dog $dog): array
    {
        $assessment = fn (?string $stored, ?bool $flag): ?string => $stored
            ?? match ($flag) {
                true => 'Positive observations',
                false => 'Not suitable',
                null => null,
            };

        $yesNo = fn (?bool $flag, string $yes = 'Yes', string $no = 'No'): ?string => match ($flag) {
            true => $yes,
            false => $no,
            null => null,
        };

        return [
            'Dog ID' => $dog->scas_id,
            'Dog name' => $dog->name,
            'Publish to bot' => $yesNo($dog->publish_to_bot),
            'Adoption status' => $dog->adoption_status,
            'Foster status' => $dog->foster_status,
            'Sex' => $dog->gender?->label(),
            'Breed or type' => $dog->breed,
            'Date of birth' => $dog->date_of_birth,
            'Birth date basis' => $dog->date_of_birth === null ? null : ($dog->date_of_birth_is_approximate ? 'Estimated' : 'Exact'),
            'Current size' => $dog->size?->label(),
            'Expected adult size' => $dog->expected_adult_size?->label(),
            'Weight (kg)' => $dog->weight_kg,
            'Date weighed' => $dog->weighed_on,
            'Energy level' => $dog->energy_level === EnergyLevel::Medium ? 'Moderate' : $dog->energy_level?->label(),
            'Personality' => $dog->personality_description,
            'Children assessment' => $assessment($dog->children_assessment, $dog->good_with_children),
            'Children observations' => $dog->children_observations,
            'Dogs assessment' => $assessment($dog->dogs_assessment, $dog->good_with_dogs),
            'Dogs observations' => $dog->dogs_observations,
            'Cats assessment' => $assessment($dog->cats_assessment, $dog->good_with_cats),
            'Cats observations' => $dog->cats_observations,
            'Home requirements' => $dog->home_requirements,
            'Owner experience' => $dog->owner_experience,
            'Alone time observations' => $dog->alone_time_observations,
            'House training' => $dog->house_training ?? $yesNo($dog->potty_trained),
            'Lead walking' => $dog->lead_walking ?? $yesNo($dog->leash_trained, 'Trained', 'In progress'),
            'Basic commands' => $dog->basic_commands_notes,
            'Vaccination status' => $dog->vaccination_status
                ?? ($dog->vaccinations->isEmpty() ? null : ($dog->vaccinations_up_to_date ? 'Up to date' : 'Due')),
            'Neutering status' => $yesNo($dog->medicalProfile?->spayed_neutered) ?? 'Unknown',
            'Microchip status' => $dog->microchip_status
                ?? (filled($dog->identification?->microchip_number) ? 'Yes' : null),
            'Approved care summary' => $dog->care_summary,
            'Approved feeding guidance' => $dog->feeding_guidance,
            'Known background' => $dog->rescueIntake?->rescue_story,
            'Photo URL' => $dog->register_photo_url,
            'Profile URL' => $dog->profile_url,
            'Last reviewed' => $dog->last_reviewed_on,
            'Reviewed by' => $dog->reviewed_by,
        ];
    }

    /**
     * The register's two calculated columns, on every row including the spare ones.
     */
    private function writeFormulas(Worksheet $sheet, int $lastRow): void
    {
        $id = $this->column('Dog ID');
        $name = $this->column('Dog name');
        $publish = $this->column('Publish to bot');
        $adoption = $this->column('Adoption status');
        $birth = $this->column('Date of birth');
        $age = $this->column('Age (months)');
        $sequence = $this->column('Bot sequence (calculated)');

        for ($row = 2; $row <= $lastRow; $row++) {
            $previous = $row - 1;
            $sheet->setCellValue("{$age}{$row}", "=IF({$birth}{$row}=\"\",\"\",IF({$birth}{$row}>TODAY(),\"Check date\",DATEDIF({$birth}{$row},TODAY(),\"m\")))");
            $sheet->setCellValue("{$sequence}{$row}", "=IF(AND({$id}{$row}<>\"\",{$name}{$row}<>\"\",{$publish}{$row}=\"Yes\",{$adoption}{$row}=\"Available\"),COUNT(\${$sequence}\$1:{$sequence}{$previous})+1,\"\")");
        }
    }

    /**
     * Row height, wrapping, banded stripes and white gridlines, as in the
     * register's table style; plus the date, weight and ID number formats.
     */
    private function styleRows(Worksheet $sheet, int $lastRow): void
    {
        $lastColumn = count(self::COLUMNS);

        $sheet->getStyle([1, 2, $lastColumn, $lastRow])->applyFromArray([
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ]);

        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(37.5);

            if ($row % 2 === 0) {
                $sheet->getStyle([1, $row, $lastColumn, $row])->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::STRIPE_FILL);
            }
        }

        foreach (self::COLUMNS as $header => [, $kind]) {
            $range = $this->column($header).'2:'.$this->column($header).$lastRow;

            match ($kind) {
                'date' => $sheet->getStyle($range)->getNumberFormat()->setFormatCode('dd mmm yyyy'),
                'weight' => $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.0'),
                'id' => $sheet->getStyle($range)->getNumberFormat()->setFormatCode('@'),
                default => null,
            };
        }

        $sheet->getStyle($this->column('Age (months)').'2:'.$this->column('Age (months)').$lastRow)
            ->getNumberFormat()->setFormatCode('0');
    }

    private function addDropdowns(Worksheet $sheet, int $lastRow): void
    {
        foreach (self::DROPDOWNS as $header => $choices) {
            $validation = (new DataValidation)
                ->setType(DataValidation::TYPE_LIST)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setFormula1('"'.implode(',', $choices).'"');

            $sheet->setDataValidation($this->column($header).'2:'.$this->column($header).$lastRow, $validation);
        }

        $weight = (new DataValidation)
            ->setType(DataValidation::TYPE_DECIMAL)
            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
            ->setAllowBlank(true)
            ->setFormula1('0');

        $sheet->setDataValidation($this->column('Weight (kg)').'2:'.$this->column('Weight (kg)').$lastRow, $weight);
    }

    /**
     * The register's highlighting: published dogs, adopted and reserved
     * dogs, birth dates in the future and duplicate Dog IDs.
     */
    private function addHighlighting(Worksheet $sheet, int $lastRow): void
    {
        $id = $this->column('Dog ID');
        $birth = $this->column('Date of birth');

        $rules = [
            [$this->column('Publish to bot'), Conditional::CONDITION_CELLIS, '"Yes"', 'FFE7F2E8', 'FF256437'],
            [$this->column('Adoption status'), Conditional::CONDITION_CELLIS, '"Adopted"', 'FFE7ECEF', 'FF536171'],
            [$this->column('Adoption status'), Conditional::CONDITION_CELLIS, '"Reserved"', 'FFFFF0C2', 'FF805900'],
            [$birth, Conditional::CONDITION_EXPRESSION, "AND(ISNUMBER({$birth}2),{$birth}2>TODAY())", 'FFFCE8E6', 'FF9B1C1C'],
            [$id, Conditional::CONDITION_EXPRESSION, "AND({$id}2<>\"\",COUNTIF(\${$id}\$2:\${$id}\${$lastRow},{$id}2)>1)", 'FFFCE8E6', 'FF9B1C1C'],
        ];

        $byRange = [];

        foreach ($rules as [$column, $type, $formula, $fill, $font]) {
            $conditional = (new Conditional)->setConditionType($type)->addCondition($formula);

            if ($type === Conditional::CONDITION_CELLIS) {
                $conditional->setOperatorType(Conditional::OPERATOR_EQUAL);
            }

            $conditional->getStyle()->getFont()->setBold(true)->getColor()->setARGB($font);
            $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setARGB($fill);
            $conditional->getStyle()->getFill()->getStartColor()->setARGB($fill);
            $byRange["{$column}2:{$column}{$lastRow}"][] = $conditional;
        }

        foreach ($byRange as $range => $conditionals) {
            $sheet->getStyle($range)->setConditionalStyles($conditionals);
        }
    }

    private function column(string $header): string
    {
        return Coordinate::stringFromColumnIndex(array_search($header, array_keys(self::COLUMNS), true) + 1);
    }
}
