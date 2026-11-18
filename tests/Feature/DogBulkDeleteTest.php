<?php

use App\Models\AuditLog;
use App\Models\Dog;
use App\Models\DogDocument;
use App\Models\DogPhoto;
use App\Models\DogVaccination;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('selected dogs are permanently deleted with their records and files', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['name' => 'Tessa', 'archived_at' => now()]);
    $otherDog = Dog::factory()->create();
    $keptDog = Dog::factory()->create();

    Storage::disk('local')->put("dog-photos/{$dog->id}/a.jpg", 'photo');
    Storage::disk('local')->put("dog-documents/{$dog->id}/a.pdf", 'document');
    DogPhoto::factory()->for($dog)->create(['file_path' => "dog-photos/{$dog->id}/a.jpg"]);
    DogDocument::factory()->for($dog)->create(['file_path' => "dog-documents/{$dog->id}/a.pdf"]);
    $vaccination = DogVaccination::factory()->for($dog)->create();

    $this->actingAs($user)
        ->from(route('dogs.index', ['archived' => 'only']))
        ->delete(route('dogs.bulk-destroy'), ['ids' => [$dog->id, $otherDog->id]])
        ->assertRedirect(route('dogs.index', ['archived' => 'only']))
        ->assertSessionHas('success', '2 dogs permanently deleted.');

    expect(Dog::query()->pluck('id')->all())->toBe([$keptDog->id])
        ->and($vaccination->fresh())->toBeNull()
        ->and(AuditLog::query()->where('action', 'dog.deleted')->where('description', "Deleted dog Tessa ({$dog->scas_id})")->exists())->toBeTrue();

    Storage::disk('local')->assertMissing("dog-photos/{$dog->id}/a.jpg");
    Storage::disk('local')->assertMissing("dog-documents/{$dog->id}/a.pdf");
});

test('users without permission to manage dogs cannot bulk delete', function () {
    $user = User::factory()->readOnly()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)
        ->delete(route('dogs.bulk-destroy'), ['ids' => [$dog->id]])
        ->assertForbidden();

    expect($dog->fresh())->not->toBeNull();
});

test('bulk delete requires a selection of existing dogs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('dogs.bulk-destroy'), ['ids' => []])
        ->assertSessionHasErrors('ids');

    $this->actingAs($user)
        ->delete(route('dogs.bulk-destroy'), ['ids' => [999999]])
        ->assertSessionHasErrors('ids.0');
});
