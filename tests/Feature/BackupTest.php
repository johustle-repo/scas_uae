<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\BackupManager;
use Illuminate\Support\Facades\Storage;

/**
 * Store a backup archive where the backup destination lists it.
 */
function storeBackupArchive(string $file): void
{
    Storage::disk('backups')->put(config('backup.backup.name').'/'.$file, 'zip-bytes');
}

test('only administrators can manage backups', function () {
    $user = User::factory()->role(UserRole::RecordsManager)->create();

    $this->actingAs($user)->get(route('settings.backups.index'))->assertForbidden();
});

test('administrators see stored backups newest first with their health', function () {
    Storage::fake('backups');
    $this->travelTo('2026-11-21 09:00:00');
    storeBackupArchive('2026-11-19-01-30-00.zip');
    storeBackupArchive('2026-11-21-01-30-00.zip');

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('settings.backups.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('settings/backups/index')
        ->where('healthy', true)
        ->where('backups.0.file', '2026-11-21-01-30-00.zip')
        ->where('backups.1.file', '2026-11-19-01-30-00.zip')
        ->where('totalSize', 18));
});

test('backups older than a day are reported as unhealthy', function () {
    Storage::fake('backups');
    $this->travelTo('2026-11-21 09:00:00');
    storeBackupArchive('2026-11-19-01-30-00.zip');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('settings.backups.index'))
        ->assertInertia(fn ($page) => $page->where('healthy', false));
});

test('running a backup reports success', function () {
    $this->mock(BackupManager::class)->shouldReceive('run')->once();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('settings.backups.store'))
        ->assertSessionHas('success', 'Backup completed.');
});

test('a failed backup is shown to the administrator', function () {
    $this->mock(BackupManager::class)->shouldReceive('run')->once()->andThrow(new RuntimeException('mysqldump not found'));

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('settings.backups.store'))
        ->assertSessionHasErrors(['backup' => 'The backup failed: mysqldump not found']);
});

test('a stored backup can be downloaded and the download is logged', function () {
    Storage::fake('backups');
    storeBackupArchive('2026-11-21-01-30-00.zip');
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('settings.backups.download', '2026-11-21-01-30-00.zip'));

    $response->assertDownload('2026-11-21-01-30-00.zip');
    expect($response->streamedContent())->toBe('zip-bytes');
    $this->assertDatabaseHas('audit_logs', ['action' => 'backup.downloaded', 'user_id' => $admin->id]);
});

test('files that are not stored backups cannot be downloaded', function () {
    Storage::fake('backups');
    Storage::disk('backups')->put('other/secret.zip', 'x');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('settings.backups.download', 'secret.zip'))
        ->assertNotFound();
});

test('a stored backup can be deleted', function () {
    Storage::fake('backups');
    storeBackupArchive('2026-11-21-01-30-00.zip');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('settings.backups.destroy', '2026-11-21-01-30-00.zip'))
        ->assertSessionHas('success', 'Backup deleted.');

    Storage::disk('backups')->assertMissing(config('backup.backup.name').'/2026-11-21-01-30-00.zip');
    $this->assertDatabaseHas('audit_logs', ['action' => 'backup.deleted']);
});
