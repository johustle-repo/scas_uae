<?php

namespace App\Http\Controllers;

use App\Enums\DogStatus;
use App\Models\Dog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard overview.
     */
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'total' => Dog::query()->active()->count(),
                'atScas' => Dog::query()->active()->status(DogStatus::AtScas)->count(),
                'inFoster' => Dog::query()->active()->whereIn('current_status', [DogStatus::LocalFoster, DogStatus::InternationalFoster])->count(),
                'adopted' => Dog::query()->active()->whereIn('current_status', [DogStatus::AdoptedUae, DogStatus::AdoptedInternationally])->count(),
                'internationalPlacements' => Dog::query()->active()->whereIn('current_status', [DogStatus::InternationalFoster, DogStatus::AdoptedInternationally])->count(),
                'needsReview' => Dog::query()->active()
                    ->where(fn ($query) => $query->needsReview()->orWhere(fn ($query) => $query->incomplete()))
                    ->count(),
            ],
            'recentlyUpdated' => Dog::query()
                ->active()
                ->with('identification')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
