<?php

namespace App\Services;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes entries to the audit trail (spec §19).
 */
class AuditLogger
{
    protected static bool $enabled = true;

    /**
     * Record one audited action.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?int $dogId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): ?AuditLog {
        if (! static::$enabled) {
            return null;
        }

        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'dog_id' => $dogId,
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * Run a callback without writing automatic audit entries, e.g. during bulk imports
     * that record a single summary entry instead.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutAuditing(Closure $callback): mixed
    {
        $previous = static::$enabled;
        static::$enabled = false;

        try {
            return $callback();
        } finally {
            static::$enabled = $previous;
        }
    }
}
