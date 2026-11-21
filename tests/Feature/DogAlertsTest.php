<?php

use App\Enums\DocumentCategory;
use App\Enums\DogStatus;
use App\Models\Dog;
use App\Models\DogDocument;
use App\Models\DogFosterRecord;
use App\Models\DogIdentification;
use App\Models\DogMedicalProfile;
use App\Models\DogVaccination;
use App\Models\User;

/**
 * Alert details grouped by alert key, from the alerts page.
 *
 * @return array<string, list<string>>
 */
function alertDetails(): array
{
    $groups = test()->actingAs(User::factory()->readOnly()->create())
        ->get(route('alerts.index'))
        ->viewData('page')['props']['groups'];

    return collect($groups)->mapWithKeys(fn (array $group): array => [
        $group['key'] => array_map(fn (array $item): string => "{$item['scas_id']}: {$item['detail']}", $group['items']),
    ])->all();
}

/**
 * A dog with complete basic details, so it raises no "needs update" alert.
 *
 * @param  array<string, mixed>  $attributes
 */
function completeDog(array $attributes = []): Dog
{
    $dog = Dog::factory()->create([
        'breed' => 'Local Mix', 'gender' => 'female', 'size' => 'medium', 'date_of_birth' => '2022-01-01', ...$attributes,
    ]);
    DogIdentification::factory()->for($dog)->create();

    return $dog;
}

beforeEach(fn () => $this->travelTo('2026-11-21 09:00:00'));

test('overdue and upcoming vaccinations are listed for dogs in care', function () {
    $dog = completeDog(['scas_id' => 'SCAS-001']);
    DogVaccination::factory()->for($dog)->create(['vaccine_details' => 'Rabies', 'administered_date' => '2025-10-01', 'next_due_date' => '2026-10-01']);
    DogVaccination::factory()->for($dog)->create(['vaccine_details' => 'DHPP', 'administered_date' => '2025-12-01', 'next_due_date' => '2026-12-01']);
    $adopted = completeDog(['scas_id' => 'SCAS-002', 'current_status' => DogStatus::AdoptedUae]);
    DogVaccination::factory()->for($adopted)->create(['vaccine_details' => 'Rabies', 'administered_date' => '2025-10-01', 'next_due_date' => '2026-10-01']);
    DogDocument::factory()->for($adopted)->create(['category' => DocumentCategory::AdoptionForm]);

    $alerts = alertDetails();

    expect($alerts['vaccination_overdue'])->toBe(['SCAS-001: Rabies was due 1 Oct 2026'])
        ->and($alerts['vaccination_due'])->toBe(['SCAS-001: DHPP due 1 Dec 2026']);
});

test('old vet checks and long foster placements need follow-up', function () {
    $dog = completeDog(['scas_id' => 'SCAS-010', 'current_status' => DogStatus::LocalFoster]);
    DogMedicalProfile::factory()->for($dog)->create(['last_vet_check_date' => '2025-06-01', 'treatment_required' => null]);
    DogFosterRecord::factory()->for($dog)->create(['foster_family_name' => 'Smith Family', 'start_date' => '2026-06-01', 'end_date' => null]);
    DogDocument::factory()->for($dog)->create(['category' => DocumentCategory::FosterAgreement]);

    $alerts = alertDetails();

    expect($alerts['medical_follow_up'])->toBe(['SCAS-010: Last vet check 1 Jun 2025'])
        ->and($alerts['foster_review'])->toBe(['SCAS-010: With Smith Family since 1 Jun 2026']);
});

test('placements without their required documents are flagged', function () {
    completeDog(['scas_id' => 'SCAS-020', 'current_status' => DogStatus::AdoptedInternationally]);

    expect(alertDetails()['missing_documents'])
        ->toBe(['SCAS-020: Adopted Internationally: no Adoption form or Pet passport on file']);
});

test('incomplete records are flagged and archived dogs raise no alerts', function () {
    Dog::factory()->create(['scas_id' => 'SCAS-030', 'breed' => null, 'gender' => 'male', 'size' => 'small', 'date_of_birth' => '2022-01-01']);
    Dog::factory()->create(['scas_id' => 'SCAS-031', 'breed' => null, 'archived_at' => now()]);

    expect(alertDetails()['needs_update'])->toBe(['SCAS-030: Missing breed, microchip']);
});

test('the dashboard shows alert counts', function () {
    completeDog(['scas_id' => 'SCAS-020', 'current_status' => DogStatus::AdoptedInternationally]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->missing('alerts')
            ->loadDeferredProps(fn ($reload) => $reload->where('alerts', [[
                'key' => 'missing_documents',
                'title' => 'Missing documentation',
                'tone' => 'warning',
                'count' => 1,
            ]])));
});
