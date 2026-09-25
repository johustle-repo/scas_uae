<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\IdentificationUpdateRequest;
use App\Models\Dog;
use Illuminate\Http\RedirectResponse;

class IdentificationController extends Controller
{
    /**
     * Update (or create) the dog's identification record.
     */
    public function update(IdentificationUpdateRequest $request, Dog $dog): RedirectResponse
    {
        $dog->identification()->updateOrCreate([], $request->validated());

        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'identification'])
            ->with('success', 'Identification details saved.');
    }
}
