<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\DogBulkDestroyRequest;
use App\Models\Dog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkDeleteController extends Controller
{
    /**
     * Permanently delete the selected dogs along with their records and files.
     */
    public function __invoke(DogBulkDestroyRequest $request): RedirectResponse
    {
        $dogs = Dog::query()
            ->with(['photos:id,dog_id,file_path', 'documents:id,dog_id,file_path'])
            ->whereKey($request->validated('ids'))
            ->get();

        $filePaths = $dogs->flatMap(fn (Dog $dog): array => [
            $dog->photo_path,
            ...$dog->photos->pluck('file_path'),
            ...$dog->documents->pluck('file_path'),
        ])->filter()->unique()->values()->all();

        DB::transaction(fn () => $dogs->each(fn (Dog $dog) => $dog->delete()));

        Storage::disk('local')->delete($filePaths);
        $dogs->each(function (Dog $dog): void {
            Storage::disk('local')->deleteDirectory("dog-photos/{$dog->id}");
            Storage::disk('local')->deleteDirectory("dog-documents/{$dog->id}");
        });

        return back()->with('success', $dogs->count().' '.Str::plural('dog', $dogs->count()).' permanently deleted.');
    }
}
