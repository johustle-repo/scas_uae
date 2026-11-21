<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Dog;
use App\Models\DogIdentification;
use App\Models\DogVaccination;
use App\Models\User;
use App\Support\DogDataExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Download the export and load it back as a workbook.
 */
function downloadDogExport(User $user, string $format = 'records', string $filename = 'scas-dogs'): Spreadsheet
{
    $response = test()->actingAs($user)->get(route('dogs.export', $format));

    $response->assertOk();
    $response->assertDownload($filename.'-'.now()->format('Y-m-d').'.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'dog-export');
    file_put_contents($path, $response->streamedContent());

    try {
        return IOFactory::load($path);
    } finally {
        unlink($path);
    }
}

test('active dog data can be exported as a full records workbook without archived dogs', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['name' => 'Abby', 'date_of_birth' => '2025-01-10']);
    DogIdentification::factory()->for($dog)->create(['microchip_number' => '900200000000001']);
    DogVaccination::factory()->for($dog)->create(['vaccine_details' => 'Rabies']);
    Dog::factory()->create(['name' => 'Goldie', 'archived_at' => now()]);

    $workbook = downloadDogExport($user);

    expect($workbook->getSheetNames())
        ->toBe(['Dogs', 'Treatments & Vaccinations', 'Foster Records', 'Adoption Records']);

    $dogs = $workbook->getSheetByName('Dogs')->toArray();
    $headings = $dogs[0];
    $abby = collect($dogs)->firstWhere(array_search('Name', $headings), 'Abby');

    expect(count($dogs))->toBe(2)
        ->and($abby[array_search('SCAS ID', $headings)])->toBe($dog->scas_id)
        ->and($abby[array_search('Date Of Birth', $headings)])->toBe('2025-01-10')
        ->and($abby[array_search('Microchip Number', $headings)])->toBe('900200000000001')
        ->and(collect($dogs)->pluck(array_search('Name', $headings)))->not->toContain('Goldie')
        ->and($headings)->not->toContain('Archived');

    $vaccinations = $workbook->getSheetByName('Treatments & Vaccinations')->toArray();

    expect($vaccinations[1][0])->toBe($dog->scas_id)
        ->and($vaccinations[1][2])->toBe('Rabies');

    expect(AuditLog::where('action', 'export.dogs')->count())->toBe(1);
});

test('all dog data can be exported as a pdf report', function () {
    $user = User::factory()->create();
    Dog::factory()->create(['name' => 'Abby']);

    $response = $this->actingAs($user)->get(route('dogs.export', 'pdf'));

    $response->assertOk();
    $response->assertDownload('scas-dogs-'.now()->format('Y-m-d').'.pdf');
    expect($response->streamedContent())->toStartWith('%PDF');
    expect(AuditLog::where('action', 'export.dogs')->sole()->description)->toContain('PDF');
});

test('each pdf profile only lists the records of its own dog', function () {
    $abby = Dog::factory()->create(['name' => 'Abby']);
    DogVaccination::factory()->for($abby)->create(['vaccine_details' => 'Rabies']);
    Dog::factory()->create(['name' => 'Goldie']);

    $profiles = collect(app(DogDataExport::class)->profiles())->keyBy('details.Name');
    $abbyTreatments = $profiles['Abby']['related']['Treatments & Vaccinations'];

    expect($profiles)->toHaveCount(2)
        ->and($profiles['Abby']['details']['SCAS ID'])->toBe($abby->scas_id)
        ->and($abbyTreatments['rows'])->toHaveCount(1)
        ->and($abbyTreatments['rows'][0][array_search('Vaccine Details', $abbyTreatments['headings'])])->toBe('Rabies')
        ->and($profiles['Goldie']['related'])->toBe([]);
});

test('an unsupported export format is not found', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dogs/export/csv')->assertNotFound();
});

test('users who cannot manage dogs cannot export data', function (string $format) {
    $user = User::factory()->role(UserRole::MedicalStaff)->create();

    $this->actingAs($user)->get(route('dogs.export', $format))->assertForbidden();
})->with(['excel', 'records', 'pdf']);

test('guests cannot export data', function () {
    $this->get(route('dogs.export', 'excel'))->assertRedirect(route('login'));
});

test('the excel export uses the dog register layout', function () {
    Dog::factory()->create([
        'scas_id' => 'SCAS-084', 'name' => 'Sindy', 'gender' => 'female', 'energy_level' => 'medium',
        'date_of_birth' => '2023-12-01', 'date_of_birth_is_approximate' => false, 'good_with_children' => true,
    ]);
    Dog::factory()->create(['scas_id' => 'SCAS-085', 'archived_at' => now()]);

    $sheet = downloadDogExport(User::factory()->create(), 'excel', 'scas-dog-register')->getSheetByName('Dog Register');

    expect($sheet->rangeToArray('A1:AM1')[0])->toBe([
        'Dog ID', 'Dog name', 'Publish to bot', 'Adoption status', 'Foster status', 'Sex', 'Breed or type', 'Date of birth',
        'Birth date basis', 'Age (months)', 'Current size', 'Expected adult size', 'Weight (kg)', 'Date weighed', 'Energy level',
        'Personality', 'Children assessment', 'Children observations', 'Dogs assessment', 'Dogs observations', 'Cats assessment',
        'Cats observations', 'Home requirements', 'Owner experience', 'Alone time observations', 'House training', 'Lead walking',
        'Basic commands', 'Vaccination status', 'Neutering status', 'Microchip status', 'Approved care summary',
        'Approved feeding guidance', 'Known background', 'Photo URL', 'Profile URL', 'Last reviewed', 'Reviewed by',
        'Bot sequence (calculated)',
    ]);
    expect($sheet->getCell('A2')->getValue())->toBe('SCAS-084')
        ->and($sheet->getCell('A3')->getValue())->toBeNull()
        ->and($sheet->getCell('F2')->getValue())->toBe('Female')
        ->and($sheet->getCell('H2')->getFormattedValue())->toBe('01 Dec 2023')
        ->and($sheet->getCell('I2')->getValue())->toBe('Exact')
        ->and($sheet->getCell('O2')->getValue())->toBe('Moderate')
        ->and($sheet->getCell('Q2')->getValue())->toBe('Positive observations')
        ->and($sheet->getCell('J2')->getValue())->toStartWith('=IF(H2="","",')
        ->and($sheet->getCell('AM2')->getValue())->toStartWith('=IF(AND(A2<>"",B2<>"",C2="Yes",D2="Available")')
        ->and($sheet->getCell('F2')->getDataValidation()->getFormula1())->toBe('"Male,Female,Unknown"')
        ->and($sheet->getStyle('A1')->getFill()->getStartColor()->getARGB())->toBe('FF283B53');
});
