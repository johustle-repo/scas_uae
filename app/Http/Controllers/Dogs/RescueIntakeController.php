<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\RescueIntakeUpdateRequest;
use App\Models\Dog;
use Illuminate\Http\RedirectResponse;

class RescueIntakeController extends Controller
{
    /**
     * Update (or create) the dog's rescue & intake record.
     */
    public function update(RescueIntakeUpdateRequest $request, Dog $dog): RedirectResponse
    {
        $dog->rescueIntake()->updateOrCreate([], $request->validated());

        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'rescue-history'])
            ->with('success', 'Rescue details saved.');
    }
}
