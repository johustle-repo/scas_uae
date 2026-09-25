<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Change the signed-in user's password after confirming their current one.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update(['password' => Hash::make($request->string('password'))]);

        AuditLogger::record('user.password_changed', 'Changed own password', subject: $user);

        return redirect()->route('settings.profile.edit')
            ->with('success', 'Password updated.');
    }
}
