<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use Illuminate\Http\RedirectResponse;

class ArchiveController extends Controller
{
    /**
     * Archive the dog, hiding it from lists and statistics.
     */
    public function store(Dog $dog): RedirectResponse
    {
        $dog->update(['archived_at' => now()]);

        return redirect()->route('dogs.show', $dog)
            ->with('success', "{$dog->name} was archived.");
    }

    /**
     * Restore an archived dog to the active records.
     */
    public function destroy(Dog $dog): RedirectResponse
    {
        $dog->update(['archived_at' => null]);

        return redirect()->route('dogs.show', $dog)
            ->with('success', "{$dog->name} was restored.");
    }
}
