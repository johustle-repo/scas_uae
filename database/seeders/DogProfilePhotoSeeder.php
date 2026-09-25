<?php

namespace Database\Seeders;

use App\Enums\PhotoKind;
use App\Models\Dog;
use App\Services\AuditLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Gives every dog without a profile photo a placeholder picture from the free
 * Dog CEO API (https://dog.ceo), matched to the dog's breed where possible.
 *
 * Dogs that already have a photo are never touched, so re-running only fills
 * new gaps. Seeded photos carry the PLACEHOLDER_CAPTION so staff can tell them
 * apart and replace them with real pictures. Not called from DatabaseSeeder —
 * run it with `php artisan db:seed --class=DogProfilePhotoSeeder`.
 */
class DogProfilePhotoSeeder extends Seeder
{
    public const PLACEHOLDER_CAPTION = 'Placeholder photo (seeded) — replace with a real picture';

    private const API_URL = 'https://dog.ceo/api/breed/%s/images';

    private const FALLBACK_BREEDS = ['mix', 'dingo'];

    /**
     * Keywords in our breed names mapped to Dog CEO breed paths.
     *
     * @var array<string, string>
     */
    private const BREED_MAP = [
        'saluki' => 'saluki',
        'husky' => 'husky',
        'german shepherd' => 'german/shepherd',
        'belgian' => 'malinois',
        'pointer' => 'pointer/german',
        'canaan' => 'dingo',
        'desert' => 'mix',
    ];

    /**
     * Image URLs per Dog CEO breed, shuffled and consumed so dogs of the same
     * breed get different pictures.
     *
     * @var array<string, Collection<int, string>>
     */
    private array $imagePools = [];

    public function run(): void
    {
        $dogs = Dog::query()->whereNull('photo_path')->orderBy('id')->get();

        if ($dogs->isEmpty()) {
            $this->command?->info('Every dog already has a profile photo.');

            return;
        }

        $seeded = 0;

        AuditLogger::withoutAuditing(function () use ($dogs, &$seeded): void {
            foreach ($dogs as $dog) {
                if ($this->seedPhoto($dog)) {
                    $seeded++;
                }
            }
        });

        $this->command?->info("Seeded placeholder profile photos for {$seeded} of {$dogs->count()} dogs.");
    }

    private function seedPhoto(Dog $dog): bool
    {
        $imageUrl = $this->nextImageUrl($this->breedPathFor($dog->breed));

        if ($imageUrl === null) {
            $this->command?->warn("No image available for {$dog->name}; skipped.");

            return false;
        }

        try {
            $response = Http::timeout(20)->retry(2, 500)->get($imageUrl);
        } catch (Throwable $exception) {
            $this->command?->warn("Could not download a photo for {$dog->name}: {$exception->getMessage()}");

            return false;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            $this->command?->warn("Could not download a photo for {$dog->name}; skipped.");

            return false;
        }

        $extension = Str::lower(pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) ?: 'jpg';
        $path = "dog-photos/{$dog->id}/seeded-".Str::random(12).".{$extension}";

        Storage::disk('local')->put($path, $response->body());

        DB::transaction(function () use ($dog, $path): void {
            $dog->photos()->create([
                'kind' => PhotoKind::Profile,
                'file_path' => $path,
                'caption' => self::PLACEHOLDER_CAPTION,
            ]);

            $dog->update(['photo_path' => $path]);
        });

        return true;
    }

    private function breedPathFor(?string $breed): string
    {
        $breed = Str::lower((string) $breed);

        foreach (self::BREED_MAP as $keyword => $path) {
            if (str_contains($breed, $keyword)) {
                return $path;
            }
        }

        return self::FALLBACK_BREEDS[array_rand(self::FALLBACK_BREEDS)];
    }

    private function nextImageUrl(string $breedPath): ?string
    {
        foreach ([$breedPath, ...self::FALLBACK_BREEDS] as $path) {
            $pool = $this->imagePools[$path] ??= $this->fetchImageUrls($path);

            if ($pool->isNotEmpty()) {
                return $pool->shift();
            }
        }

        return null;
    }

    /**
     * @return Collection<int, string>
     */
    private function fetchImageUrls(string $breedPath): Collection
    {
        try {
            $urls = Http::timeout(20)->retry(2, 500)
                ->get(sprintf(self::API_URL, $breedPath))
                ->json('message');
        } catch (Throwable) {
            return collect();
        }

        return collect(is_array($urls) ? $urls : [])->filter(fn (mixed $url): bool => is_string($url))->shuffle()->values();
    }
}
