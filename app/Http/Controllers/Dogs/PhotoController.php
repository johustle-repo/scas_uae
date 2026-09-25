<?php

namespace App\Http\Controllers\Dogs;

use App\Enums\PhotoKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dogs\PhotoStoreRequest;
use App\Models\Dog;
use App\Models\DogPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    /**
     * Serve the dog's current profile photo.
     */
    public function profile(Dog $dog): StreamedResponse
    {
        abort_if(blank($dog->photo_path) || ! Storage::disk('local')->exists($dog->photo_path), 404);

        return $this->serve($dog->photo_path);
    }

    /**
     * Serve one of the dog's photos.
     */
    public function show(Dog $dog, DogPhoto $photo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->file_path), 404);

        return $this->serve($photo->file_path);
    }

    /**
     * Upload one or more photos. A new profile photo becomes the dog's avatar, and so
     * does the first gallery photo of a dog that has no avatar yet.
     */
    public function store(PhotoStoreRequest $request, Dog $dog): RedirectResponse
    {
        $kind = PhotoKind::from($request->validated('kind'));

        /** @var list<UploadedFile> $files */
        $files = $request->file('photos');

        DB::transaction(function () use ($files, $dog, $kind, $request): void {
            foreach ($files as $file) {
                $photo = $dog->photos()->create([
                    'kind' => $kind,
                    'file_path' => $file->store("dog-photos/{$dog->id}", 'local')
                        ?: throw new RuntimeException('Could not store photo.'),
                    'caption' => $request->validated('caption'),
                    'uploaded_by' => $request->user()?->id,
                ]);

                $becomesProfile = $kind === PhotoKind::Profile
                    || ($kind === PhotoKind::Gallery && blank($dog->photo_path));

                if ($becomesProfile) {
                    $dog->update(['photo_path' => $photo->file_path]);
                }
            }
        });

        $count = count($files);

        return back()->with('success', $count === 1 ? 'Photo uploaded.' : "{$count} photos uploaded.");
    }

    /**
     * Use an existing photo as the dog's profile photo.
     */
    public function makeProfile(Dog $dog, DogPhoto $photo): RedirectResponse
    {
        $dog->update(['photo_path' => $photo->file_path]);

        return back()->with('success', 'Profile photo updated.');
    }

    /**
     * Delete a photo and its file.
     */
    public function destroy(Dog $dog, DogPhoto $photo): RedirectResponse
    {
        DB::transaction(function () use ($dog, $photo): void {
            if ($dog->photo_path === $photo->file_path) {
                $dog->update(['photo_path' => null]);
            }

            $photo->delete();
        });

        Storage::disk('local')->delete($photo->file_path);

        return back()->with('success', 'Photo removed.');
    }

    protected function serve(string $path): StreamedResponse
    {
        return Storage::disk('local')->response($path, headers: [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
