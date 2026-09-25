<?php

use App\Enums\ImportFlagType;
use App\Models\Dog;
use App\Models\DogImportFlag;
use App\Models\DogVaccination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function writeLegacyFixtureRow(mixed $sheet, int $row, array $values): void
{
    foreach ($values as $column => $value) {
        $sheet->setCellValue($column.$row, $value);
    }
}

function buildLegacyFixtureWorkbook(): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('DOGS');

    // Row 1 intentionally blank (matches the real workbook's layout).
    writeLegacyFixtureRow($sheet, 2, [
        'A' => 'Photos', 'B' => 'Microchip Number', 'C' => 'Date of Microchipping', 'D' => 'Chip Location',
        'E' => 'Name', 'F' => 'Species', 'G' => 'Breed', 'H' => 'N/S', 'I' => 'Date of N/S', 'J' => 'Gender',
        'K' => 'Date of Birth', 'L' => 'Age', 'M' => 'Colour/Markings', 'N' => 'Vaccine Details',
        'O' => 'Vaccination Date', 'P' => 'Next Vaccination DD', 'Q' => 'Passport Details',
        'R' => 'PCC-Number/Tag Number', 'S' => "Owner's Name", 'T' => 'Address', 'U' => 'Tel. No.', 'V' => 'Story',
    ]);

    // Clean primary dog with two vaccination continuation rows.
    writeLegacyFixtureRow($sheet, 3, [
        'B' => '111111111111111', 'C' => '01.01.2024', 'D' => 'Neck', 'E' => 'Rex', 'F' => 'Canine',
        'G' => 'Labrador', 'H' => 'Neutered', 'I' => '02.01.2024', 'J' => 'Male', 'K' => '01.01.2020',
        'M' => 'Black', 'N' => 'Rabies', 'O' => '01.01.2024', 'P' => '01.01.2025', 'R' => 'PC1001',
        'V' => 'Rescued from the street.',
    ]);
    writeLegacyFixtureRow($sheet, 4, [
        'N' => 'Distemper', 'O' => '02.01.2024', 'P' => '02.01.2025',
    ]);

    // Corrupted decimal DOB + an annotation-only vaccination date.
    writeLegacyFixtureRow($sheet, 5, [
        'B' => '222222222222222', 'E' => 'Bella', 'F' => 'Canine', 'G' => 'Mix', 'J' => 'Female',
        'K' => '8.2019000000000004', 'N' => 'Rabies', 'O' => '(Valid Until) 01.01.2026',
    ]);

    // Near-empty rows, same shape as the real "Gray"/"Coco" rows.
    writeLegacyFixtureRow($sheet, 6, ['B' => '1', 'E' => 'Gray']);
    writeLegacyFixtureRow($sheet, 7, ['B' => '1', 'E' => 'Coco', 'V' => 'Coco was given as a gift, but left behind.']);

    // Footer summary row that must be excluded.
    writeLegacyFixtureRow($sheet, 8, ['A' => 'Dog Count:', 'B' => '4']);

    $path = storage_path('framework/testing/legacy-dogs-sample-'.uniqid().'.xlsx');
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

test('imports dogs from the legacy workbook, handling every documented data problem', function () {
    $path = buildLegacyFixtureWorkbook();

    $this->artisan('dogs:import-legacy', ['path' => $path])->assertSuccessful();

    expect(Dog::count())->toBe(4)
        ->and(Dog::where('name', 'like', '%Dog Count%')->exists())->toBeFalse();

    $rex = Dog::where('name', 'Rex')->firstOrFail();
    expect($rex->vaccinations)->toHaveCount(2);

    $bella = Dog::where('name', 'Bella')->firstOrFail();
    expect($bella->date_of_birth?->toDateString())->toBe('2019-08-01')
        ->and($bella->date_of_birth_is_approximate)->toBeTrue()
        ->and($bella->importFlags->pluck('flag_type'))->toContain(ImportFlagType::ApproximateDob);

    $bellaVaccination = $bella->vaccinations->first();
    expect($bellaVaccination->next_due_date)->toBeNull()
        ->and($bellaVaccination->expiry_annotation)->toBe('(Valid Until) 01.01.2026');

    $gray = Dog::where('name', 'Gray')->firstOrFail();
    expect($gray->identification?->microchip_number)->toBeNull()
        ->and($gray->importFlags->pluck('flag_type'))
        ->toContain(ImportFlagType::PlaceholderMicrochip)
        ->toContain(ImportFlagType::IncompleteRecord);

    $coco = Dog::where('name', 'Coco')->firstOrFail();
    expect($coco->rescueIntake?->rescue_story)->toBe('Coco was given as a gift, but left behind.');

    unlink($path);
});

test('re-running the import is idempotent', function () {
    $path = buildLegacyFixtureWorkbook();

    $this->artisan('dogs:import-legacy', ['path' => $path])->assertSuccessful();
    $firstRunDogCount = Dog::count();
    $firstRunFlagCount = DogImportFlag::count();
    $firstRunVaccinationCount = DogVaccination::count();

    $this->artisan('dogs:import-legacy', ['path' => $path])->assertSuccessful();

    expect(Dog::count())->toBe($firstRunDogCount)
        ->and(DogImportFlag::count())->toBe($firstRunFlagCount)
        ->and(DogVaccination::count())->toBe($firstRunVaccinationCount);

    unlink($path);
});

test('dry run makes no database changes', function () {
    $path = buildLegacyFixtureWorkbook();

    $this->artisan('dogs:import-legacy', ['path' => $path, '--dry-run' => true])->assertSuccessful();

    expect(Dog::count())->toBe(0);

    unlink($path);
});
