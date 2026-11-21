<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;

/**
 * Runs and lists backups of the database and uploaded files (spec §17),
 * stored on the private "backups" disk.
 */
class BackupManager
{
    public const DISK = 'backups';

    /**
     * Newest first.
     *
     * @return list<array{file: string, size: int, created_at: string}>
     */
    public function backups(): array
    {
        $backups = [];

        foreach ($this->destination()->backups() as $backup) {
            $backups[] = [
                'file' => basename($backup->path()),
                'size' => (int) $backup->sizeInBytes(),
                'created_at' => $backup->date()->toIso8601String(),
            ];
        }

        return $backups;
    }

    /**
     * Whether the newest backup is less than a day old (the monitor's rule).
     */
    public function isHealthy(): bool
    {
        $newest = $this->destination()->newestBackup();

        return $newest !== null && $newest->date()->gt(now()->subDay());
    }

    /**
     * Back up the database and uploaded files now.
     *
     * @throws RuntimeException when the backup command fails
     */
    public function run(): void
    {
        $exitCode = Artisan::call('backup:run', ['--disable-notifications' => true]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'The backup did not complete.');
        }
    }

    /**
     * Find a stored backup by its file name, so a request can never reach
     * files outside the backup folder.
     */
    public function find(string $file): ?Backup
    {
        return $this->destination()->backups()
            ->first(fn (Backup $backup): bool => basename($backup->path()) === $file);
    }

    private function destination(): BackupDestination
    {
        return BackupDestination::create(self::DISK, (string) config('backup.backup.name'));
    }
}
