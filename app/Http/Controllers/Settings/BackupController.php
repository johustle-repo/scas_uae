<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Services\BackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Backup status, the stored backups and recent backup activity.
     */
    public function index(BackupManager $backups): Response
    {
        $stored = $backups->backups();

        return Inertia::render('settings/backups/index', [
            'backups' => $stored,
            'healthy' => $backups->isHealthy(),
            'totalSize' => array_sum(array_column($stored, 'size')),
            'history' => AuditLog::query()
                ->with('user:id,name')
                ->where('action', 'like', 'backup.%')
                ->latest('created_at')
                ->latest('id')
                ->limit(20)
                ->get(['id', 'user_id', 'action', 'description', 'created_at']),
        ]);
    }

    /**
     * Run a manual backup now.
     */
    public function store(BackupManager $backups): RedirectResponse
    {
        set_time_limit(300);

        try {
            $backups->run();
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['backup' => 'The backup failed: '.$exception->getMessage()]);
        }

        return back()->with('success', 'Backup completed.');
    }

    /**
     * Download a stored backup archive.
     */
    public function download(BackupManager $backups, string $file): StreamedResponse
    {
        $backup = $backups->find($file) ?? abort(404);

        AuditLogger::record('backup.downloaded', "Downloaded backup {$file}");

        return response()->streamDownload(function () use ($backup): void {
            $stream = $backup->stream();
            fpassthru($stream);
            fclose($stream);
        }, $file, ['Content-Type' => 'application/zip']);
    }

    /**
     * Delete a stored backup archive.
     */
    public function destroy(BackupManager $backups, string $file): RedirectResponse
    {
        $backup = $backups->find($file) ?? abort(404);

        $backup->delete();
        AuditLogger::record('backup.deleted', "Deleted backup {$file}");

        return back()->with('success', 'Backup deleted.');
    }
}
