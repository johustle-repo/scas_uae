<?php

use App\Models\Dog;
use Database\Seeders\DogProfilePhotoSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('only dogs without a profile photo get a placeholder', function () {
    Storage::fake('local');
    Http::fake([
        'dog.ceo/api/*' => Http::response(['message' => ['https://images.dog.ceo/breeds/husky/a.jpg', 'https://images.dog.ceo/breeds/husky/b.jpg'], 'status' => 'success']),
        'images.dog.ceo/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);
    $withoutPhoto = Dog::factory()->create(['breed' => 'Husky', 'photo_path' => null]);
    $withPhoto = Dog::factory()->create(['photo_path' => 'dog-photos/existing.jpg']);

    $this->seed(DogProfilePhotoSeeder::class);

    $withoutPhoto->refresh();
    expect($withoutPhoto->photo_path)->toStartWith("dog-photos/{$withoutPhoto->id}/seeded-")
        ->and($withoutPhoto->photos()->sole()->caption)->toBe(DogProfilePhotoSeeder::PLACEHOLDER_CAPTION)
        ->and($withPhoto->fresh()->photo_path)->toBe('dog-photos/existing.jpg')
        ->and($withPhoto->photos()->count())->toBe(0);
    Storage::disk('local')->assertExists($withoutPhoto->photo_path);
});

test('a failed download leaves the dog without a photo', function () {
    Storage::fake('local');
    Http::fake([
        'dog.ceo/api/*' => Http::response(['message' => ['https://images.dog.ceo/breeds/mix/a.jpg'], 'status' => 'success']),
        'images.dog.ceo/*' => Http::response('', 500),
    ]);
    $dog = Dog::factory()->create(['photo_path' => null]);

    $this->seed(DogProfilePhotoSeeder::class);

    expect($dog->fresh()->photo_path)->toBeNull()
        ->and($dog->photos()->count())->toBe(0);
});
