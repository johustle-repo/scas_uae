<?php

namespace App\Http\Controllers;

use App\Enums\DogStatus;
use App\Enums\ImportFlagType;
use App\Models\Dog;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Dogs shown to the public as looking for a home.
     */
    public const ADOPTABLE_STATUSES = [DogStatus::AtScas, DogStatus::ReturnedRehoming];

    /**
     * Adopted dogs shown as success stories.
     */
    public const ADOPTED_STATUSES = [DogStatus::AdoptedUae, DogStatus::AdoptedInternationally];

    private const MAX_ADOPTABLE = 48;

    private const MAX_HAPPY_TAILS = 6;

    /**
     * The public adoption landing page. Only non-sensitive profile fields are
     * exposed: no microchip, medical, location or adopter details.
     */
    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'adoptableDogs' => $this->publicDogs(self::ADOPTABLE_STATUSES)
                ->limit(self::MAX_ADOPTABLE)
                ->get()
                ->map(fn (Dog $dog): array => $this->present($dog))
                ->values(),
            'happyTails' => $this->publicDogs(self::ADOPTED_STATUSES)
                ->whereNotNull('photo_path')
                ->limit(self::MAX_HAPPY_TAILS)
                ->get()
                ->map(fn (Dog $dog): array => $this->present($dog))
                ->values(),
            'stats' => [
                'available' => self::listedDogs(self::ADOPTABLE_STATUSES)->count(),
                'adopted' => self::listedDogs(self::ADOPTED_STATUSES)->count(),
                'inFoster' => Dog::query()->active()->status([DogStatus::LocalFoster, DogStatus::InternationalFoster])->count(),
            ],
            'contact' => array_filter(config('scas.adoption_contact')),
        ]);
    }

    /**
     * @param  list<DogStatus>  $statuses
     * @return Builder<Dog>
     */
    private function publicDogs(array $statuses): Builder
    {
        return self::listedDogs($statuses)
            ->orderByRaw('photo_path is null')
            ->latest('updated_at');
    }

    /**
     * Dogs that may appear publicly with the given statuses. Imported dogs whose
     * placement is still unconfirmed are held back until staff review them.
     *
     * @param  list<DogStatus>  $statuses
     * @return Builder<Dog>
     */
    public static function listedDogs(array $statuses): Builder
    {
        return Dog::query()
            ->active()
            ->status($statuses)
            ->whereDoesntHave('importFlags', fn (Builder $query) => $query->where('flag_type', ImportFlagType::PossiblePlacementUnconfirmed));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Dog $dog): array
    {
        return [
            'id' => $dog->id,
            'name' => $dog->name,
            'breed' => $dog->breed,
            'gender' => $dog->gender?->value,
            'size' => $dog->size?->label(),
            'age' => $dog->age,
            'is_puppy' => $dog->date_of_birth !== null && $dog->date_of_birth->diffInMonths(now()) < 12,
            'photo_url' => filled($dog->photo_path) ? route('adopt.photo', ['dog' => $dog, 'v' => $dog->updated_at?->getTimestamp()], absolute: false) : null,
            'personality' => $dog->personality_description,
            'energy_level' => $dog->energy_level?->label(),
            'good_with' => array_keys(array_filter([
                'dogs' => $dog->good_with_dogs,
                'cats' => $dog->good_with_cats,
                'children' => $dog->good_with_children,
            ])),
            'house_trained' => (bool) $dog->potty_trained,
            'is_rehoming' => $dog->current_status === DogStatus::ReturnedRehoming,
        ];
    }
}
