<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    private const RECENT_ACTIVITY_LIMIT = 8;

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'sessions' => $this->sessions($request),
            'recentActivity' => AuditLog::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->latest('id')
                ->limit(self::RECENT_ACTIVITY_LIMIT)
                ->get(['id', 'action', 'description', 'ip_address', 'created_at']),
            'lastLoginAt' => AuditLog::query()
                ->where('user_id', $user->id)
                ->where('action', 'auth.login')
                ->latest('created_at')
                ->latest('id')
                ->skip(1)
                ->value('created_at'),
        ]);
    }

    /**
     * Update the user's name and email address.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());
        $user->save();

        return redirect()->route('settings.profile.edit')
            ->with('success', 'Profile updated.');
    }

    /**
     * The user's signed-in browsers, newest activity first. Only available
     * with the database session driver; other drivers report none.
     *
     * @return list<array{id: string, ip_address: string|null, browser: string, platform: string, is_mobile: bool, is_current: bool, last_active_at: string}>
     */
    private function sessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'id' => hash('sha256', $session->id),
                'ip_address' => $session->ip_address,
                'browser' => $this->browser((string) $session->user_agent),
                'platform' => $this->platform((string) $session->user_agent),
                'is_mobile' => (bool) preg_match('/Mobile|Android|iPhone|iPad/i', (string) $session->user_agent),
                'is_current' => $session->id === $request->session()->getId(),
                'last_active_at' => CarbonImmutable::createFromTimestamp($session->last_activity)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function browser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };
    }

    private function platform(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };
    }
}
