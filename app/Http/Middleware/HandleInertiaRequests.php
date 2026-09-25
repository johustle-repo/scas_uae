<?php

namespace App\Http\Middleware;

use App\Enums\AdoptionApplicationStatus;
use App\Enums\Permission;
use App\Models\AdoptionApplication;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roleLabel' => $user?->role->label(),
                'permissions' => $user === null
                    ? []
                    : collect(Permission::cases())
                        ->filter(fn (Permission $permission): bool => $user->hasPermission($permission))
                        ->map(fn (Permission $permission): string => $permission->value)
                        ->values(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'sidebarOpen' => $request->cookie('sidebar_open', 'true') === 'true',
            'newAdoptionRequests' => fn (): int => $user?->hasPermission(Permission::ManagePlacements)
                ? AdoptionApplication::query()->where('status', AdoptionApplicationStatus::New)->count()
                : 0,
        ];
    }
}
