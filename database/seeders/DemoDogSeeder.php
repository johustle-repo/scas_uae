<?php

namespace Database\Seeders;

use App\Enums\DogStatus;
use App\Enums\PlacementType;
use App\Models\Dog;
use App\Models\DogAdoptionRecord;
use App\Models\DogFosterRecord;
use App\Models\DogIdentification;
use App\Models\DogMedicalProfile;
use App\Models\DogRescueIntake;
use App\Models\DogVaccination;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds clearly-marked demo dogs (SCAS IDs "DEMO-###") with complete
 * profiles, vaccination history and placement records across every status.
 *
 * Re-running replaces only the DEMO-### dogs; real and imported records are
 * never touched. Not called from DatabaseSeeder so production seeding stays
 * free of fake data — run it with `php artisan db:seed --class=DemoDogSeeder`.
 */
class DemoDogSeeder extends Seeder
{
    private const ID_PREFIX = 'DEMO-';

    /**
     * @var list<array{name: string, status: DogStatus, location: string}>
     */
    private const DOGS = [
        ['name' => 'Biscuit', 'status' => DogStatus::AtScas, 'location' => 'Ras Al Khaimah'],
        ['name' => 'Sahara', 'status' => DogStatus::AtScas, 'location' => 'Ras Al Khaimah'],
        ['name' => 'Zaatar', 'status' => DogStatus::AtScas, 'location' => 'Ras Al Khaimah'],
        ['name' => 'Nala', 'status' => DogStatus::LocalFoster, 'location' => 'Dubai'],
        ['name' => 'Dune', 'status' => DogStatus::LocalFoster, 'location' => 'Sharjah'],
        ['name' => 'Hazel', 'status' => DogStatus::LocalFoster, 'location' => 'Abu Dhabi'],
        ['name' => 'Oscar', 'status' => DogStatus::InternationalFoster, 'location' => 'Berlin, Germany'],
        ['name' => 'Pepper', 'status' => DogStatus::InternationalFoster, 'location' => 'London, United Kingdom'],
        ['name' => 'Ziggy', 'status' => DogStatus::InternationalFoster, 'location' => 'Amsterdam, Netherlands'],
        ['name' => 'Luna', 'status' => DogStatus::AdoptedUae, 'location' => 'Dubai'],
        ['name' => 'Toffee', 'status' => DogStatus::AdoptedUae, 'location' => 'Al Ain'],
        ['name' => 'Khalifa', 'status' => DogStatus::AdoptedUae, 'location' => 'Abu Dhabi'],
        ['name' => 'Maple', 'status' => DogStatus::AdoptedInternationally, 'location' => 'Toronto, Canada'],
        ['name' => 'Rocco', 'status' => DogStatus::AdoptedInternationally, 'location' => 'Munich, Germany'],
        ['name' => 'Saffron', 'status' => DogStatus::AdoptedInternationally, 'location' => 'Dublin, Ireland'],
        ['name' => 'Bruno', 'status' => DogStatus::ReturnedRehoming, 'location' => 'Ras Al Khaimah'],
        ['name' => 'Cinnamon', 'status' => DogStatus::ReturnedRehoming, 'location' => 'Ras Al Khaimah'],
        ['name' => 'Shadow', 'status' => DogStatus::Memorial, 'location' => 'Ras Al Khaimah'],
        ['name' => 'Goldie', 'status' => DogStatus::Memorial, 'location' => 'Ras Al Khaimah'],
    ];

    /**
     * Core vaccines and how many months each dose protects for.
     *
     * @var array<string, int>
     */
    private const VACCINES = [
        'Nobivac Rabies' => 12,
        'Nobivac DHPPi' => 12,
        'Nobivac Lepto L4' => 12,
        'Nobivac KC (Kennel Cough)' => 12,
    ];

    public function run(): void
    {
        fake()->seed(2026);

        AuditLogger::withoutAuditing(function (): void {
            DB::transaction(function (): void {
                Dog::query()->where('scas_id', 'like', self::ID_PREFIX.'%')->delete();

                foreach (self::DOGS as $index => $entry) {
                    $this->seedDog($index + 1, $entry);
                }
            });
        });

        $this->command?->info(count(self::DOGS).' demo dogs seeded (SCAS IDs '.self::ID_PREFIX.'001–'.sprintf('%03d', count(self::DOGS)).').');
    }

    /**
     * @param  array{name: string, status: DogStatus, location: string}  $entry
     */
    private function seedDog(int $number, array $entry): void
    {
        $status = $entry['status'];
        $received = CarbonImmutable::instance(fake()->dateTimeBetween('-3 years', '-4 months'))->startOfDay();

        $dog = Dog::factory()->create([
            'scas_id' => self::ID_PREFIX.sprintf('%03d', $number),
            'name' => $entry['name'],
            'current_status' => $status,
            'current_location' => $entry['location'],
            'date_of_birth' => $received->subMonths(fake()->numberBetween(4, 60)),
            'date_of_birth_is_approximate' => fake()->boolean(40),
        ]);

        DogIdentification::factory()->for($dog)->create([
            'microchip_date' => $received->addDays(fake()->numberBetween(1, 10)),
        ]);

        DogRescueIntake::factory()->for($dog)->create([
            'date_received' => $received,
            'intake_date' => $received->addDay(),
        ]);

        DogMedicalProfile::factory()->for($dog)->create([
            'spayed_neutered' => true,
            'spayed_neutered_date' => $received->addWeeks(fake()->numberBetween(2, 8)),
            'veterinary_clinic' => fake()->randomElement([
                'British Veterinary Hospital, Dubai',
                'Modern Vet Clinic, Dubai',
                'RAK Veterinary Clinic',
                'Sharjah Cat & Dog Clinic',
            ]),
            'last_vet_check_date' => CarbonImmutable::instance(fake()->dateTimeBetween('-8 months', '-1 week')),
        ]);

        $this->seedVaccinations($dog, $received, $status);
        $this->seedPlacements($dog, $received, $status, $entry['location']);
    }

    /**
     * Yearly boosters from intake onwards. The last round is deliberately
     * skipped for some dogs so the list shows overdue and due-soon states.
     */
    private function seedVaccinations(Dog $dog, CarbonImmutable $received, DogStatus $status): void
    {
        $firstDose = $received->addDays(fake()->numberBetween(3, 14));
        $skipLatestRound = $status !== DogStatus::Memorial && fake()->boolean(30);

        for ($dose = $firstDose; $dose->isPast(); $dose = $dose->addYear()) {
            if ($skipLatestRound && $dose->notEqualTo($firstDose) && $dose->addYear()->isFuture()) {
                break;
            }

            foreach (self::VACCINES as $vaccine => $months) {
                DogVaccination::factory()->for($dog)->create([
                    'vaccine_details' => $vaccine,
                    'administered_date' => $dose,
                    'next_due_date' => $dose->addMonths($months),
                    'notes' => 'Batch '.fake()->bothify('??####'),
                ]);
            }
        }
    }

    private function seedPlacements(Dog $dog, CarbonImmutable $received, DogStatus $status, string $location): void
    {
        $isInternational = in_array($status, [DogStatus::InternationalFoster, DogStatus::AdoptedInternationally], true);
        $placementType = $isInternational ? PlacementType::International : PlacementType::Local;
        $placedOn = $received->addMonths(fake()->numberBetween(1, 3));

        match ($status) {
            DogStatus::LocalFoster, DogStatus::InternationalFoster => DogFosterRecord::factory()->for($dog)->create([
                'foster_type' => $placementType,
                'location' => $location,
                'start_date' => $placedOn,
                'end_date' => null,
            ]),
            DogStatus::AdoptedUae, DogStatus::AdoptedInternationally => $this->seedAdoptionAfterFoster($dog, $placedOn, $placementType, $location),
            DogStatus::ReturnedRehoming => DogAdoptionRecord::factory()->for($dog)->create([
                'adoption_date' => $placedOn,
                'return_date' => $placedOn->addMonths(fake()->numberBetween(2, 6)),
                'return_reason' => fake()->randomElement([
                    'Family relocating abroad and unable to take the dog.',
                    'New baby in the household; owners could not manage the dog\'s energy level.',
                ]),
                'notes' => 'Returned to SCAS and available for rehoming.',
            ]),
            DogStatus::Memorial => $dog->medicalProfile()->update([
                'medical_notes' => 'Passed away peacefully at SCAS in '.$placedOn->addMonths(6)->format('F Y').'. Much loved by the team.',
            ]),
            default => null,
        };
    }

    private function seedAdoptionAfterFoster(Dog $dog, CarbonImmutable $placedOn, PlacementType $placementType, string $location): void
    {
        $adoptedOn = $placedOn->addMonths(fake()->numberBetween(1, 4));

        DogFosterRecord::factory()->for($dog)->create([
            'foster_type' => $placementType,
            'location' => $location,
            'start_date' => $placedOn,
            'end_date' => $adoptedOn,
            'notes' => 'Foster ended when the dog was adopted.',
        ]);

        DogAdoptionRecord::factory()->for($dog)->create([
            'adoption_type' => $placementType,
            'adopter_location' => $location,
            'adoption_date' => $adoptedOn,
        ]);
    }
}
