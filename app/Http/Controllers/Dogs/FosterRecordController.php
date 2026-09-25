<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\FosterRecordStoreRequest;
use App\Models\Dog;
use App\Models\DogFosterRecord;
use App\Services\DogPlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FosterRecordController extends Controller
{
    public function __construct(protected DogPlacementService $placementService) {}

    /**
     * Store a new foster placement for the dog.
     */
    public function store(FosterRecordStoreRequest $request, Dog $dog): RedirectResponse
    {
        DB::transaction(function () use ($request, $dog): void {
            $dog->fosterRecords()->create($request->validated());
            $this->placementService->syncCurrentPlacement($dog);
        });

        return $this->backToFosterHistory($dog, 'Foster placement added.');
    }

    /**
     * Update an existing foster placement.
     */
    public function update(FosterRecordStoreRequest $request, Dog $dog, DogFosterRecord $fosterRecord): RedirectResponse
    {
        DB::transaction(function () use ($request, $dog, $fosterRecord): void {
            $fosterRecord->update($request->validated());
            $this->placementService->syncCurrentPlacement($dog);
        });

        return $this->backToFosterHistory($dog, 'Foster placement updated.');
    }

    /**
     * End an active foster placement.
     */
    public function close(Request $request, Dog $dog, DogFosterRecord $fosterRecord): RedirectResponse
    {
        $validated = $request->validate([
            'end_date' => ['required', 'date', 'after_or_equal:'.($fosterRecord->start_date?->toDateString() ?? '1900-01-01')],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $dog, $fosterRecord): void {
            $fosterRecord->update([
                'end_date' => $validated['end_date'],
                'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : $fosterRecord->notes,
            ]);
            $this->placementService->syncCurrentPlacement($dog);
        });

        return $this->backToFosterHistory($dog, 'Foster placement closed.');
    }

    protected function backToFosterHistory(Dog $dog, string $message): RedirectResponse
    {
        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'foster-history'])
            ->with('success', $message);
    }
}
