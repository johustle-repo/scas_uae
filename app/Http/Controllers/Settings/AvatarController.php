<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController extends Controller
{
    /**
     * Serve a user's profile photo to any signed-in team member.
     */
    public function show(User $user): StreamedResponse
    {
        abort_if(blank($user->avatar_path) || ! Storage::disk('local')->exists($user->avatar_path), 404);

        return Storage::disk('local')->response($user->avatar_path, headers: [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /**
     * Upload or replace the signed-in user's profile photo.
     */
    public function store(AvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->avatar_path;

        $user->update([
            'avatar_path' => $request->file('avatar')->store("avatars/{$user->id}", 'local')
                ?: throw new RuntimeException('Could not store profile photo.'),
        ]);

        if (filled($previousPath)) {
            Storage::disk('local')->delete($previousPath);
        }

        return back()->with('success', 'Profile photo updated.');
    }

    /**
     * Remove the signed-in user's profile photo.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $path = $user->avatar_path;

        if (filled($path)) {
            $user->update(['avatar_path' => null]);
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
