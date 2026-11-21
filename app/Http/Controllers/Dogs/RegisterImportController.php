<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\DogRegisterImportRequest;
use App\Support\DogRegister\DogRegisterImporter;
use App\Support\DogRegister\DogRegisterImportException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Two-step Dog Register import: the upload is checked and previewed without
 * saving anything, then imported once staff confirm it.
 */
class RegisterImportController extends Controller
{
    /** Folder on the private disk holding uploads awaiting confirmation. */
    private const FOLDER = 'imports';

    private const SESSION_KEY = 'dogRegisterImport';

    /**
     * Store the upload and preview what importing it would change.
     */
    public function preview(DogRegisterImportRequest $request, DogRegisterImporter $importer): RedirectResponse
    {
        $this->discardPending($request);
        $this->pruneAbandonedUploads();

        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['file' => 'Upload one Dog Register file.']);
        }

        $extension = Str::lower($file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx';
        $path = $file->storeAs(self::FOLDER, Str::uuid().'.'.$extension, 'local')
            ?: throw ValidationException::withMessages(['file' => 'The file could not be saved. Try again.']);

        try {
            $result = $importer->import(Storage::disk('local')->path($path), dryRun: true);
        } catch (DogRegisterImportException $exception) {
            Storage::disk('local')->delete($path);

            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $request->session()->put(self::SESSION_KEY, [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);

        return back()->with('importPreview', [
            'fileName' => $file->getClientOriginalName(),
            ...$result->toArray(),
        ]);
    }

    /**
     * Import the previewed upload.
     */
    public function confirm(Request $request, DogRegisterImporter $importer): RedirectResponse
    {
        /** @var array{path: string, name: string|null}|null $pending */
        $pending = $request->session()->pull(self::SESSION_KEY);

        if ($pending === null || ! Storage::disk('local')->exists($pending['path'])) {
            throw ValidationException::withMessages(['file' => 'That upload has expired. Upload the Dog Register again.']);
        }

        try {
            $result = $importer->import(Storage::disk('local')->path($pending['path']));
        } catch (DogRegisterImportException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        } finally {
            Storage::disk('local')->delete($pending['path']);
        }

        return back()
            ->with('success', $result->summary())
            ->with('importResult', $result->toArray());
    }

    /**
     * Throw away the previewed upload without importing it.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $this->discardPending($request);

        return back();
    }

    private function discardPending(Request $request): void
    {
        /** @var array{path: string}|null $pending */
        $pending = $request->session()->pull(self::SESSION_KEY);

        if ($pending !== null) {
            Storage::disk('local')->delete($pending['path']);
        }
    }

    /**
     * Delete uploads that were previewed but never confirmed or cancelled.
     */
    private function pruneAbandonedUploads(): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files(self::FOLDER) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }
}
