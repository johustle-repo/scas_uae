<?php

use App\Enums\PhotoKind;
use App\Models\Dog;
use App\Models\DogDocument;
use App\Models\DogPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('a document can be uploaded, viewed and downloaded', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.documents.store', $dog), [
        'category' => 'vaccination_certificate',
        'title' => 'Rabies certificate',
        'file' => UploadedFile::fake()->create('rabies.pdf', 200, 'application/pdf'),
    ])->assertRedirect(route('dogs.show', ['dog' => $dog, 'tab' => 'documents']));

    $document = $dog->documents()->sole();

    expect($document)
        ->title->toBe('Rabies certificate')
        ->original_name->toBe('rabies.pdf')
        ->uploaded_by->toBe($user->id);
    Storage::disk('local')->assertExists($document->file_path);

    $this->actingAs($user)->get(route('dogs.documents.show', [$dog, $document]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'inline; filename=rabies.pdf');

    $this->actingAs($user)->get(route('dogs.documents.download', [$dog, $document]))
        ->assertDownload('rabies.pdf');
});

test('documents are only available to signed-in users', function () {
    $document = DogDocument::factory()->create();

    $this->get(route('dogs.documents.download', [$document->dog_id, $document]))
        ->assertRedirect(route('login'));
});

test('unsupported or oversized files are rejected', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.documents.store', $dog), [
        'category' => 'other',
        'file' => UploadedFile::fake()->create('script.exe', 10),
    ])->assertSessionHasErrors('file');

    $this->actingAs($user)->post(route('dogs.documents.store', $dog), [
        'category' => 'other',
        'file' => UploadedFile::fake()->create('huge.pdf', 20_000, 'application/pdf'),
    ])->assertSessionHasErrors('file');

    expect($dog->documents()->count())->toBe(0);
});

test('a document file can be replaced and removed', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.documents.store', $dog), [
        'category' => 'adoption_form',
        'file' => UploadedFile::fake()->create('form-v1.pdf', 50, 'application/pdf'),
    ]);

    $document = $dog->documents()->sole();
    $firstPath = $document->file_path;

    $this->actingAs($user)->post(route('dogs.documents.replace', [$dog, $document]), [
        'file' => UploadedFile::fake()->create('form-v2.pdf', 50, 'application/pdf'),
    ]);

    $document->refresh();
    expect($document->original_name)->toBe('form-v2.pdf');
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($document->file_path);

    $this->actingAs($user)->delete(route('dogs.documents.destroy', [$dog, $document]));

    expect(DogDocument::find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($document->file_path);
});

test('read-only users can view documents but not upload them', function () {
    $user = User::factory()->readOnly()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.documents.store', $dog), [
        'category' => 'other',
        'file' => UploadedFile::fake()->create('note.pdf', 10, 'application/pdf'),
    ])->assertForbidden();
});

test('uploading a profile photo sets the dog\'s avatar', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.photos.store', $dog), [
        'kind' => PhotoKind::Profile->value,
        'photos' => [UploadedFile::fake()->image('tessa.jpg')],
    ])->assertRedirect();

    $photo = $dog->photos()->sole();
    $dog->refresh();

    expect($dog->photo_path)->toBe($photo->file_path)
        ->and($dog->photo_url)->toStartWith('/dogs/'.$dog->id.'/photo');

    $this->actingAs($user)->get(route('dogs.photo', $dog))->assertOk();
});

test('several rescue photos can be uploaded at once and one removed', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.photos.store', $dog), [
        'kind' => PhotoKind::Rescue->value,
        'caption' => 'Day of rescue',
        'photos' => [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.png'),
        ],
    ]);

    expect($dog->photos()->where('kind', PhotoKind::Rescue)->count())->toBe(2)
        ->and($dog->fresh()->photo_path)->toBeNull();

    $photo = $dog->photos()->first();

    $this->actingAs($user)->delete(route('dogs.photos.destroy', [$dog, $photo]));

    expect(DogPhoto::find($photo->id))->toBeNull();
    Storage::disk('local')->assertMissing($photo->file_path);
});

test('a gallery photo can be made the profile photo', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();
    $photo = DogPhoto::factory()->for($dog)->create();

    $this->actingAs($user)->patch(route('dogs.photos.profile', [$dog, $photo]));

    expect($dog->fresh()->photo_path)->toBe($photo->file_path);
});

test('non-image files are rejected as photos', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.photos.store', $dog), [
        'kind' => PhotoKind::Gallery->value,
        'photos' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')],
    ])->assertSessionHasErrors('photos.0');
});
