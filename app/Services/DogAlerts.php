<?php

namespace App\Services;

use App\Enums\DocumentCategory;
use App\Enums\DogStatus;
use App\Models\Dog;
use App\Models\DogAdoptionRecord;
use App\Models\DogDocument;
use App\Models\DogFosterRecord;
use App\Models\DogVaccination;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Works out the follow-ups staff need to act on (spec §20) from the current
 * records of active (non-archived) dogs.
 */
class DogAlerts
{
    /** Days ahead a vaccination counts as "due soon". */
    public const VACCINATION_DUE_DAYS = 30;

    /** Months after the last vet check that a follow-up is due. */
    public const VET_CHECK_MONTHS = 12;

    /** Days an open foster placement runs before it needs a review. */
    public const FOSTER_REVIEW_DAYS = 90;

    /** Days ahead an adoption or foster start counts as upcoming. */
    public const UPCOMING_DAYS = 14;

    /**
     * Dogs still in SCAS's care, whose health follow-ups are SCAS's job.
     *
     * @var list<DogStatus>
     */
    private const IN_CARE = [DogStatus::AtScas, DogStatus::LocalFoster, DogStatus::InternationalFoster, DogStatus::ReturnedRehoming];

    /**
     * Documents each placement status must have on file.
     *
     * @var array<string, list<DocumentCategory>>
     */
    private const REQUIRED_DOCUMENTS = [
        'local_foster' => [DocumentCategory::FosterAgreement],
        'international_foster' => [DocumentCategory::FosterAgreement, DocumentCategory::PetPassport],
        'adopted_uae' => [DocumentCategory::AdoptionForm],
        'adopted_internationally' => [DocumentCategory::AdoptionForm, DocumentCategory::PetPassport],
    ];

    /**
     * Alert groups in priority order; groups with no alerts are left out.
     *
     * @return list<array{key: string, title: string, tone: string, items: list<array{dog_id: int, scas_id: string, name: string, detail: string}>}>
     */
    public function groups(): array
    {
        $dogs = Dog::query()
            ->active()
            ->with([
                'vaccinations',
                'medicalProfile',
                'fosterRecords' => fn ($query) => $query->whereNull('end_date'),
                'adoptionRecords',
                'documents:id,dog_id,category',
                'importFlags:id,dog_id',
                'identification',
            ])
            ->orderBy('name')
            ->get();

        $groups = [
            ['key' => 'vaccination_overdue', 'title' => 'Vaccinations overdue', 'tone' => 'danger', 'items' => $this->vaccinations($dogs, overdue: true)],
            ['key' => 'vaccination_due', 'title' => 'Vaccinations due in the next '.self::VACCINATION_DUE_DAYS.' days', 'tone' => 'warning', 'items' => $this->vaccinations($dogs, overdue: false)],
            ['key' => 'medical_follow_up', 'title' => 'Medical follow-up due', 'tone' => 'warning', 'items' => $this->medicalFollowUps($dogs)],
            ['key' => 'foster_review', 'title' => 'Foster placements to review', 'tone' => 'warning', 'items' => $this->fosterReviews($dogs)],
            ['key' => 'missing_documents', 'title' => 'Missing documentation', 'tone' => 'warning', 'items' => $this->missingDocuments($dogs)],
            ['key' => 'upcoming', 'title' => 'Upcoming adoptions and transfers', 'tone' => 'info', 'items' => $this->upcoming($dogs)],
            ['key' => 'needs_update', 'title' => 'Records needing an update', 'tone' => 'neutral', 'items' => $this->recordsNeedingUpdate($dogs)],
        ];

        return array_values(array_filter($groups, fn (array $group): bool => $group['items'] !== []));
    }

    /**
     * Alert counts keyed by group, for the dashboard.
     *
     * @return list<array{key: string, title: string, tone: string, count: int}>
     */
    public function summary(): array
    {
        return array_map(fn (array $group): array => [
            'key' => $group['key'],
            'title' => $group['title'],
            'tone' => $group['tone'],
            'count' => count($group['items']),
        ], $this->groups());
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return list<array{dog_id: int, scas_id: string, name: string, detail: string}>
     */
    private function vaccinations(Collection $dogs, bool $overdue): array
    {
        $items = [];
        $dueBy = today()->addDays(self::VACCINATION_DUE_DAYS);

        foreach ($this->inCare($dogs) as $dog) {
            foreach ($dog->currentVaccinations() as $vaccination) {
                $due = $vaccination->next_due_date;

                if ($due === null) {
                    continue;
                }

                $matches = $overdue ? $due->lt(today()) : $due->gte(today()) && $due->lte($dueBy);

                if ($matches) {
                    $items[] = $this->item($dog, $this->vaccinationDetail($vaccination, $due, $overdue));
                }
            }
        }

        return $items;
    }

    private function vaccinationDetail(DogVaccination $vaccination, CarbonInterface $due, bool $overdue): string
    {
        return $vaccination->vaccine_details.($overdue ? ' was due ' : ' due ').$due->format('j M Y');
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return list<array{dog_id: int, scas_id: string, name: string, detail: string}>
     */
    private function medicalFollowUps(Collection $dogs): array
    {
        $items = [];

        foreach ($this->inCare($dogs) as $dog) {
            $lastCheck = $dog->medicalProfile?->last_vet_check_date;

            if ($lastCheck !== null && $lastCheck->lt(today()->subMonths(self::VET_CHECK_MONTHS))) {
                $items[] = $this->item($dog, 'Last vet check '.$lastCheck->format('j M Y'));
            }

            if (filled($dog->medicalProfile?->treatment_required)) {
                $items[] = $this->item($dog, 'Treatment required: '.$dog->medicalProfile->treatment_required);
            }
        }

        return $items;
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return list<array{dog_id: int, scas_id: string, name: string, detail: string}>
     */
    private function fosterReviews(Collection $dogs): array
    {
        $items = [];

        foreach ($dogs as $dog) {
            foreach ($dog->fosterRecords as $foster) {
                /** @var DogFosterRecord $foster */
                if ($foster->start_date !== null && $foster->start_date->lt(today()->subDays(self::FOSTER_REVIEW_DAYS))) {
                    $items[] = $this->item($dog, "With {$foster->foster_family_name} since ".$foster->start_date->format('j M Y'));
                }
            }
        }

        return $items;
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return list<array{dog_id: int, scas_id: string, name: string, detail: string}>
     */
    private function missingDocuments(Collection $dogs): array
    {
        $items = [];

        foreach ($dogs as $dog) {
            $required = self::REQUIRED_DOCUMENTS[$dog->current_status->value] ?? [];
            $onFile = $dog->documents->map(fn (DogDocument $document): DocumentCategory => $document->category);
            $missing = array_filter($required, fn (DocumentCategory $category): bool => ! $onFile->contains($category));

            if ($missing !== []) {
                $items[] = $this->item($dog, $dog->current_status->label().': no '.implode(' or ', array_map(fn (DocumentCategory $category): string => $category->label(), $missing)).' on file');
            }
        }

        return $items;
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return list<array{dog_id: int, scas_id: string, name: string, detail: string}>
     */
    private function upcoming(Collection $dogs): array
    {
        $items = [];
        $until = today()->addDays(self::UPCOMING_DAYS);

        foreach ($dogs as $dog) {
            foreach ($dog->adoptionRecords as $adoption) {
                /** @var DogAdoptionRecord $adoption */
                if ($adoption->adoption_date !== null && $adoption->adoption_date->gte(today()) && $adoption->adoption_date->lte($until)) {
                    $items[] = $this->item($dog, "Adoption by {$adoption->adopter_name} on ".$adoption->adoption_date->format('j M Y'));
                }
            }

            foreach ($dog->fosterRecords as $foster) {
                /** @var DogFosterRecord $foster */
                if ($foster->start_date !== null && $foster->start_date->gt(today()) && $foster->start_date->lte($until)) {
                    $items[] = $this->item($dog, "Moves to {$foster->foster_family_name} on ".$foster->start_date->format('j M Y'));
                }
            }
        }

        return $items;
    }

    /**
     * Same rule as the dashboard's "Records needing update" card.
     *
     * @param  Collection<int, Dog>  $dogs
     * @return list<array{dog_id: int, scas_id: string, name: string, detail: string}>
     */
    private function recordsNeedingUpdate(Collection $dogs): array
    {
        $items = [];

        foreach ($dogs as $dog) {
            $missing = array_keys(array_filter([
                'breed' => $dog->breed === null,
                'gender' => $dog->gender === null,
                'size' => $dog->size === null,
                'date of birth' => $dog->date_of_birth === null,
                'microchip' => blank($dog->identification?->microchip_number),
            ]));

            if ($dog->importFlags->isNotEmpty()) {
                $items[] = $this->item($dog, 'Flagged for review during import');
            } elseif ($missing !== []) {
                $items[] = $this->item($dog, 'Missing '.implode(', ', $missing));
            }
        }

        return $items;
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     * @return Collection<int, Dog>
     */
    private function inCare(Collection $dogs): Collection
    {
        return $dogs->filter(fn (Dog $dog): bool => in_array($dog->current_status, self::IN_CARE, true));
    }

    /**
     * @return array{dog_id: int, scas_id: string, name: string, detail: string}
     */
    private function item(Dog $dog, string $detail): array
    {
        return ['dog_id' => $dog->id, 'scas_id' => $dog->scas_id, 'name' => $dog->name, 'detail' => $detail];
    }
}
