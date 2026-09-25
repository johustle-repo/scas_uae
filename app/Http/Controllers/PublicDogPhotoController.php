<?php

namespace App\Http\Controllers;

use App\Models\Dog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicDogPhotoController extends Controller
{
    /**
     * Serve the profile photo of a dog listed on the public landing page.
     * Photos of every other dog stay behind authentication.
     */
    public function __invoke(Dog $dog): StreamedResponse|RedirectResponse
    {
        $isListed = WelcomeController::listedDogs([...WelcomeController::ADOPTABLE_STATUSES, ...WelcomeController::ADOPTED_STATUSES])
            ->whereKey($dog->getKey())
            ->exists();

        abort_unless($isListed && filled($dog->photo_path), 404);

        if (preg_match('#^(https?:)?//#', $dog->photo_path) === 1 || str_starts_with($dog->photo_path, '/')) {
            return redirect()->away($dog->photo_path);
        }

        abort_unless(Storage::disk('local')->exists($dog->photo_path), 404);

        return Storage::disk('local')->response($dog->photo_path, headers: [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
