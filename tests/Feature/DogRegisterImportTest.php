<?php

use App\Enums\EnergyLevel;
use App\Enums\Gender;
use App\Enums\Size;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Dog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Build a Dog Register workbook upload from header => value rows.
 *
 * @param  list<array<string, mixed>>  $rows
 */
function dogRegisterUpload(array $rows, string $sheetTitle = 'Dog Register'): UploadedFile
{
    $headers = ['Dog ID', 'Dog name', 'Publish to bot', 'Adoption status', 'Sex', 'Breed or type', 'Date of birth',
        'Current size', 'Weight (kg)', 'Energy level', 'Personality', 'Children assessment', 'Cats assessment',
        'Lead walking', 'Neutering status', 'Known background', 'Photo URL', 'Age (months)'];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetTitle);
    $sheet->fromArray($headers, null, 'A1');

    foreach ($rows as $index => $row) {
        $sheet->fromArray(array_map(fn (string $header): mixed => $row[$header] ?? null, $headers), null, 'A'.($index + 2));
    }

    $path = storage_path('framework/testing/dog-register-'.uniqid().'.xlsx');
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'Database.xlsx', null, null, true);
}

/**
 * Upload the register for preview, then confirm the import.
 */
function importDogRegister(TestCase $test, UploadedFile $file): TestResponse
{
    $test->post(route('dogs.import'), ['file' => $file])->assertSessionHasNoErrors();

    return $test->post(route('dogs.import.confirm'));
}

test('read-only users cannot import the dog register', function () {
    $user = User::factory()->role(UserRole::ReadOnly)->create();

    $this->actingAs($user)
        ->post(route('dogs.import'), ['file' => dogRegisterUpload([['Dog ID' => 'SCAS-200', 'Dog name' => 'Nova']])])
        ->assertForbidden();

    $this->assertDatabaseCount('dogs', 0);
});

test('importing updates existing dogs, keeps values for blank cells and adds new dogs', function () {
    $user = User::factory()->create();
    $existing = Dog::factory()->create([
        'scas_id' => 'SCAS-084',
        'name' => 'Sindy',
        'breed' => 'Shepherd Mix',
        'personality_description' => 'Calm with visitors.',
        'size' => Size::Medium,
    ]);

    $response = importDogRegister($this->actingAs($user), dogRegisterUpload([
        [
            'Dog ID' => 'SCAS-084', 'Dog name' => 'Sindy', 'Breed or type' => 'German Shepherd', 'Current size' => 'Large',
            'Energy level' => 'Moderate', 'Children assessment' => 'Positive observations', 'Cats assessment' => 'Not suitable',
            'Lead walking' => 'In progress', 'Neutering status' => 'Yes', 'Weight (kg)' => 45, 'Publish to bot' => 'Yes',
            'Adoption status' => 'Available', 'Age (months)' => '=DATEDIF(G2,TODAY(),"m")',
        ],
        ['Dog ID' => 'scas-113', 'Dog name' => 'Pebble', 'Sex' => 'Female', 'Date of birth' => '2024-03-01', 'Known background' => 'Found near Sharjah.'],
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Dog Register imported: 1 added, 1 updated, 0 unchanged, 0 skipped.');
    $this->assertDatabaseCount('dogs', 2);

    $existing->refresh();
    expect($existing->breed)->toBe('German Shepherd')
        ->and($existing->personality_description)->toBe('Calm with visitors.')
        ->and($existing->size)->toBe(Size::Large)
        ->and($existing->energy_level)->toBe(EnergyLevel::Medium)
        ->and($existing->good_with_children)->toBeTrue()
        ->and($existing->good_with_cats)->toBeFalse()
        ->and($existing->cats_assessment)->toBe('Not suitable')
        ->and($existing->leash_trained)->toBeFalse()
        ->and($existing->weight_kg)->toBe('45.00')
        ->and($existing->publish_to_bot)->toBeTrue()
        ->and($existing->adoption_status)->toBe('Available')
        ->and($existing->register_imported_at)->not->toBeNull();
    expect($existing->medicalProfile?->spayed_neutered)->toBeTrue();

    $new = Dog::query()->where('scas_id', 'SCAS-113')->sole();
    expect($new->name)->toBe('Pebble')
        ->and($new->gender)->toBe(Gender::Female)
        ->and($new->date_of_birth?->toDateString())->toBe('2024-03-01')
        ->and($new->rescueIntake?->rescue_story)->toBe('Found near Sharjah.');

    $this->assertDatabaseHas('audit_logs', ['action' => 'import.dog_register', 'user_id' => $user->id]);
});

test('re-importing the same file does not add or change any dog', function () {
    $user = User::factory()->create();
    $rows = [['Dog ID' => 'SCAS-090', 'Dog name' => 'Mara', 'Sex' => 'Female', 'Energy level' => 'High']];
    importDogRegister($this->actingAs($user), dogRegisterUpload($rows));
    $auditEntries = AuditLog::query()->where('action', 'like', 'dog.%')->count();

    $response = importDogRegister($this->actingAs($user), dogRegisterUpload($rows));

    $response->assertSessionHas('success', 'Dog Register imported: 0 added, 0 updated, 1 unchanged, 0 skipped.');
    $this->assertDatabaseCount('dogs', 1);
    expect(AuditLog::query()->where('action', 'like', 'dog.%')->count())->toBe($auditEntries);
});

test('rows without a usable dog id or name are skipped', function () {
    $user = User::factory()->create();

    $response = importDogRegister($this->actingAs($user), dogRegisterUpload([
        ['Dog name' => 'No Id'],
        ['Dog ID' => 'DOG-1', 'Dog name' => 'Wrong Format'],
        ['Dog ID' => 'SCAS-120'],
        ['Dog ID' => 'SCAS-121', 'Dog name' => 'First'],
        ['Dog ID' => 'SCAS-121', 'Dog name' => 'Second'],
    ]));

    $response->assertSessionHas('importResult', fn (array $result): bool => array_column($result['skipped'], 'message') === [
        'No Dog ID, so the row was not imported.',
        'Dog ID "DOG-1" is not in the SCAS-### format.',
        'SCAS-120 is new but has no Dog name, so it was not added.',
        'SCAS-121 appears again (first on row 5); this row was ignored.',
    ]);
    expect(Dog::query()->pluck('name', 'scas_id')->all())->toBe(['SCAS-121' => 'First']);
});

test('unrecognised values are reported and do not overwrite existing data', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['scas_id' => 'SCAS-050', 'gender' => Gender::Male, 'size' => Size::Small]);

    $response = importDogRegister($this->actingAs($user), dogRegisterUpload([
        ['Dog ID' => 'SCAS-050', 'Dog name' => $dog->name, 'Sex' => 'Dog', 'Current size' => 'Unknown', 'Photo URL' => 'javascript:alert(1)'],
    ]));

    $response->assertSessionHas('importResult', fn (array $result): bool => array_column($result['warnings'], 'message') === [
        'Sex value "Dog" was not recognised and was not imported.',
        'Photo URL value "javascript:alert(1)" was not recognised and was not imported.',
    ]);
    $dog->refresh();
    expect($dog->gender)->toBe(Gender::Male)
        ->and($dog->size)->toBe(Size::Small)
        ->and($dog->register_photo_url)->toBeNull();
});

test('the upload must be an excel workbook with the dog register headers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('dogs.import'), ['file' => UploadedFile::fake()->create('dogs.pdf', 1, 'application/pdf')])
        ->assertSessionHasErrors(['file' => 'Upload the Dog Register as an Excel (.xlsx) or CSV (.csv) file.']);

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray(['Name', 'Breed'], null, 'A1');
    $path = storage_path('framework/testing/not-a-register-'.uniqid().'.xlsx');
    (new Xlsx($spreadsheet))->save($path);

    $this->actingAs($user)
        ->post(route('dogs.import'), ['file' => new UploadedFile($path, 'other.xlsx', null, null, true)])
        ->assertSessionHasErrors(['file' => 'The workbook has no "Dog ID" and "Dog name" header row. Upload the Dog Register file.']);
    $this->assertDatabaseCount('dogs', 0);
});

test('fields managed by the dog register cannot be changed from the edit form', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['name' => 'Sindy', 'breed' => 'German Shepherd', 'current_location' => 'Kennel A']);

    $this->actingAs($user)->put(route('dogs.update', $dog), [
        'name' => 'Renamed',
        'breed' => 'Poodle',
        'species' => $dog->species->value,
        'current_status' => $dog->current_status->value,
        'current_location' => 'Kennel B',
    ])->assertRedirect(route('dogs.show', $dog));

    $dog->refresh();
    expect($dog->name)->toBe('Sindy')
        ->and($dog->breed)->toBe('German Shepherd')
        ->and($dog->current_location)->toBe('Kennel B');
});

test('uploading the register previews the changes without saving anything', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['scas_id' => 'SCAS-084', 'breed' => 'Shepherd Mix']);

    $response = $this->actingAs($user)->post(route('dogs.import'), ['file' => dogRegisterUpload([
        ['Dog ID' => 'SCAS-084', 'Dog name' => $dog->name, 'Breed or type' => 'German Shepherd'],
        ['Dog ID' => 'SCAS-300', 'Dog name' => 'Nova'],
    ])]);

    $response->assertSessionHas('importPreview', fn (array $preview): bool => $preview['fileName'] === 'Database.xlsx'
        && array_column($preview['created'], 'scas_id') === ['SCAS-300']
        && $preview['updated'][0]['fields'] === ['Breed or type']);
    expect($dog->refresh()->breed)->toBe('Shepherd Mix');
    $this->assertDatabaseCount('dogs', 1);
    $this->assertDatabaseMissing('audit_logs', ['action' => 'import.dog_register']);
    expect(Storage::disk('local')->files('imports'))->toHaveCount(1);
});

test('confirming imports the previewed file and removes the upload', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('dogs.import'), ['file' => dogRegisterUpload([['Dog ID' => 'SCAS-300', 'Dog name' => 'Nova']])]);

    $response = $this->actingAs($user)->post(route('dogs.import.confirm'));

    $response->assertSessionHas('success', 'Dog Register imported: 1 added, 0 updated, 0 unchanged, 0 skipped.');
    $this->assertDatabaseHas('dogs', ['scas_id' => 'SCAS-300', 'name' => 'Nova']);
    expect(Storage::disk('local')->files('imports'))->toBeEmpty();
});

test('cancelling a preview discards the upload and imports nothing', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('dogs.import'), ['file' => dogRegisterUpload([['Dog ID' => 'SCAS-300', 'Dog name' => 'Nova']])]);

    $this->actingAs($user)->delete(route('dogs.import.cancel'));

    expect(Storage::disk('local')->files('imports'))->toBeEmpty();
    $this->actingAs($user)->post(route('dogs.import.confirm'))
        ->assertSessionHasErrors(['file' => 'That upload has expired. Upload the Dog Register again.']);
    $this->assertDatabaseCount('dogs', 0);
});

test('the register can be imported from a csv file with day-first dates', function () {
    $user = User::factory()->create();
    $path = storage_path('framework/testing/dog-register-'.uniqid().'.csv');
    file_put_contents($path, "Dog ID,Dog name,Date of birth,Sex\nSCAS-301,Luna,01/12/2023,Female\n");

    importDogRegister($this->actingAs($user), new UploadedFile($path, 'Database.csv', 'text/csv', null, true));

    $dog = Dog::query()->where('scas_id', 'SCAS-301')->sole();
    expect($dog->name)->toBe('Luna')
        ->and($dog->gender)->toBe(Gender::Female)
        ->and($dog->date_of_birth?->toDateString())->toBe('2023-12-01');
});
