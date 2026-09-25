<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdoptionApplicationStoreRequest;
use App\Models\AdoptionApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class AdoptionApplicationController extends Controller
{
    /**
     * Save an adoption request submitted from the public landing page.
     */
    public function store(AdoptionApplicationStoreRequest $request): RedirectResponse
    {
        AdoptionApplication::create([
            ...Arr::except($request->validated(), ['agree', 'website']),
            'has_garden' => $request->boolean('has_garden'),
            'has_children' => $request->boolean('has_children'),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Thank you! Your adoption request was sent.');
    }
}
