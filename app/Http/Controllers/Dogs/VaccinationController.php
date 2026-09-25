<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\VaccinationStoreRequest;
use App\Models\Dog;
use App\Models\DogVaccination;
use Illuminate\Http\RedirectResponse;

class VaccinationController extends Controller
{
    /**
     * Store a new treatment or vaccination record for the dog.
     */
    public function store(VaccinationStoreRequest $request, Dog $dog): RedirectResponse
    {
        $dog->vaccinations()->create($request->validated());

        return $this->backToMedical($dog, 'Treatment added.');
    }

    /**
     * Update an existing treatment or vaccination record.
     */
    public function update(VaccinationStoreRequest $request, Dog $dog, DogVaccination $vaccination): RedirectResponse
    {
        $vaccination->update($request->validated());

        return $this->backToMedical($dog, 'Treatment updated.');
    }

    /**
     * Delete a treatment or vaccination record.
     */
    public function destroy(Dog $dog, DogVaccination $vaccination): RedirectResponse
    {
        $vaccination->delete();

        return $this->backToMedical($dog, 'Treatment deleted.');
    }

    protected function backToMedical(Dog $dog, string $message): RedirectResponse
    {
        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'medical'])
            ->with('success', $message);
    }
}
