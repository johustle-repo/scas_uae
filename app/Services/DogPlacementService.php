<?php

namespace App\Services;

use App\Enums\DogStatus;
use App\Enums\PlacementType;
use App\Models\Dog;

class DogPlacementService
{
    /**
     * Recompute a dog's current status and location from its latest
     * adoption/foster records, keeping the denormalized "as of now"
     * cache on the dogs table in sync with its history tables.
     */
    public function syncCurrentPlacement(Dog $dog): void
    {
        $latestAdoption = $dog->adoptionRecords()
            ->orderByDesc('adoption_date')
            ->orderByDesc('id')
            ->first();

        if ($latestAdoption !== null) {
            if ($latestAdoption->return_date !== null) {
                $dog->forceFill([
                    'current_status' => DogStatus::ReturnedRehoming,
                ])->save();

                return;
            }

            $dog->forceFill([
                'current_status' => $latestAdoption->adoption_type === PlacementType::International
                    ? DogStatus::AdoptedInternationally
                    : DogStatus::AdoptedUae,
                'current_location' => $latestAdoption->adopter_location,
            ])->save();

            return;
        }

        $activeFoster = $dog->fosterRecords()
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($activeFoster !== null) {
            $dog->forceFill([
                'current_status' => $activeFoster->foster_type === PlacementType::International
                    ? DogStatus::InternationalFoster
                    : DogStatus::LocalFoster,
                'current_location' => $activeFoster->location,
            ])->save();

            return;
        }

        $dog->forceFill([
            'current_status' => DogStatus::AtScas,
        ])->save();
    }
}
