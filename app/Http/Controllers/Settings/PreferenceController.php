<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PreferenceUpdateRequest;
use Illuminate\Http\RedirectResponse;

class PreferenceController extends Controller
{
    /**
     * Save the signed-in user's display preferences.
     */
    public function __invoke(PreferenceUpdateRequest $request): RedirectResponse
    {
        $request->user()?->update($request->validated());

        return back()->with('success', 'Preferences saved.');
    }
}
