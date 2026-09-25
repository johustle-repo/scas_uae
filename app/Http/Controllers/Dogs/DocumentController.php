<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\DocumentReplaceRequest;
use App\Http\Requests\Dogs\DocumentStoreRequest;
use App\Models\Dog;
use App\Models\DogDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Upload a document to the dog's record.
     */
    public function store(DocumentStoreRequest $request, Dog $dog): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $document = $dog->documents()->create([
            'category' => $request->validated('category'),
            'title' => $request->validated('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ...$this->storeFile($file, $dog),
            'uploaded_by' => $request->user()?->id,
        ]);

        return $this->backToDocuments($dog, "“{$document->title}” was uploaded.");
    }

    /**
     * Show the document inline when the browser can display it.
     */
    public function show(Dog $dog, DogDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
            $document->isPreviewable() ? 'inline' : 'attachment',
        );
    }

    /**
     * Download the original file.
     */
    public function download(Dog $dog, DogDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    /**
     * Swap the stored file for a new version, keeping the title and category.
     */
    public function replace(DocumentReplaceRequest $request, Dog $dog, DogDocument $document): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $previousPath = $document->file_path;

        $document->update([
            ...$this->storeFile($file, $dog),
            'uploaded_by' => $request->user()?->id,
        ]);

        Storage::disk('local')->delete($previousPath);

        return $this->backToDocuments($dog, "“{$document->title}” was replaced.");
    }

    /**
     * Remove the document and its file.
     */
    public function destroy(Dog $dog, DogDocument $document): RedirectResponse
    {
        $document->delete();
        Storage::disk('local')->delete($document->file_path);

        return $this->backToDocuments($dog, "“{$document->title}” was removed.");
    }

    /**
     * @return array{file_path: string, original_name: string, mime_type: string, size: int}
     */
    protected function storeFile(UploadedFile $file, Dog $dog): array
    {
        $path = $file->store("dog-documents/{$dog->id}", 'local');

        if ($path === false) {
            throw new RuntimeException("Could not store document {$file->getClientOriginalName()}.");
        }

        return [
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) Storage::disk('local')->size($path),
        ];
    }

    protected function backToDocuments(Dog $dog, string $message): RedirectResponse
    {
        return redirect()->route('dogs.show', ['dog' => $dog, 'tab' => 'documents'])
            ->with('success', $message);
    }
}
