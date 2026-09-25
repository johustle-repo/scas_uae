<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SessionDestroyRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    /**
     * Sign the user out everywhere except this browser. Rotating the remember
     * token also invalidates "Keep me signed in" cookies on other devices.
     */
    public function destroy(SessionDestroyRequest $request): RedirectResponse
    {
        $user = $request->user();

        $revoked = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $user->setRememberToken(Str::random(60));
        $user->save();

        AuditLogger::record('auth.sessions_revoked', "Logged out {$revoked} other ".Str::plural('session', $revoked), subject: $user);

        return redirect()->route('settings.profile.edit')
            ->with('success', $revoked === 0
                ? 'No other sessions were active.'
                : "Logged out of {$revoked} other ".Str::plural('session', $revoked).'.');
    }
}
