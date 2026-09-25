<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\AdoptionRecordStoreRequest;
use App\Models\Dog;
use App\Models\DogAdoptionRecord;
use App\Services\DogPlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdoptionRecordController extends Controller
{
    public function __construct(protected DogPlacementService $placementService) {}

    /**
     * Store a new adoption record for the dog.
     */
    public function store(AdoptionRecordStoreRequest $request, Dog $dog): RedirectResponse
    {
        DB::transaction(function () use ($request, $dog): void {
            $dog->adoptionRecords()->create($request->validated());
            $this->placementService->syncCurrentPlacement($dog);
        });

        return $this->backToAdoptionHistory($dog, 'Adoption recorded.');
    }

    /**
     * Update an adoption record, including recording a return.
     */
    public function update(AdoptionRecordStoreRequest $request, Dog $dog, DogAdoptionRecord $adoptionRecord): RedirectResponse
    {
        DB::transaction(function () use ($request, $dog, $adoptionRecord): void {
            $adoptionRecord->update($request->validated());
            $this->placementService->syncCurrentPlacement($dog);
        });

        return $this->backToAdoptionHistory($dog, 'Adoption record updated.');
    }

    protected function backToAdoptionHistory(Dog $dog, string $message): RedirectResponse
    {
        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'adoption-history'])
            ->with('success', $message);
    }
}
