<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Dog;
use App\Models\DogIdentification;
use App\Models\DogVaccination;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Download the export and load it back as a workbook.
 */
function downloadDogExport(User $user): Spreadsheet
{
    $response = test()->actingAs($user)->get(route('dogs.export'));

    $response->assertOk();
    $response->assertDownload('scas-dogs-'.now()->format('Y-m-d').'.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'dog-export');
    file_put_contents($path, $response->streamedContent());

    try {
        return IOFactory::load($path);
    } finally {
        unlink($path);
    }
}

test('all dog data can be exported as a workbook', function () {
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

    expect(count($dogs))->toBe(3)
        ->and($abby[array_search('SCAS ID', $headings)])->toBe($dog->scas_id)
        ->and($abby[array_search('Date Of Birth', $headings)])->toBe('2025-01-10')
        ->and($abby[array_search('Microchip Number', $headings)])->toBe('900200000000001')
        ->and(collect($dogs)->pluck(array_search('Name', $headings)))->toContain('Goldie');

    $vaccinations = $workbook->getSheetByName('Treatments & Vaccinations')->toArray();

    expect($vaccinations[1][0])->toBe($dog->scas_id)
        ->and($vaccinations[1][2])->toBe('Rabies');

    expect(AuditLog::where('action', 'export.dogs')->count())->toBe(1);
});

test('users who cannot manage dogs cannot export data', function () {
    $user = User::factory()->role(UserRole::MedicalStaff)->create();

    $this->actingAs($user)->get(route('dogs.export'))->assertForbidden();
});

test('guests cannot export data', function () {
    $this->get(route('dogs.export'))->assertRedirect(route('login'));
});
