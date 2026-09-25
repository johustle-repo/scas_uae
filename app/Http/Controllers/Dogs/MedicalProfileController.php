<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\MedicalProfileUpdateRequest;
use App\Models\Dog;
use Illuminate\Http\RedirectResponse;

class MedicalProfileController extends Controller
{
    /**
     * Update (or create) the dog's medical profile.
     */
    public function update(MedicalProfileUpdateRequest $request, Dog $dog): RedirectResponse
    {
        $dog->medicalProfile()->updateOrCreate([], $request->validated());

        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'medical'])
            ->with('success', 'Medical information saved.');
    }
}
