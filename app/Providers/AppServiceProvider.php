<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePermissions();
        $this->configureAuthAuditing();
        $this->configureBackupAuditing();

        Authenticate::redirectUsing(fn () => route('login'));
    }

    /**
     * Record logins, logouts, failed sign-ins and lockouts in the audit trail.
     */
    protected function configureAuthAuditing(): void
    {
        Event::listen(function (Failed $event): void {
            $email = Str::limit((string) ($event->credentials['email'] ?? ''), 100);

            AuditLogger::record(
                'auth.failed',
                "Failed sign-in for \"{$email}\"",
                userId: $event->user instanceof User ? $event->user->id : null,
            );
        });

        Event::listen(function (Lockout $event): void {
            $email = Str::limit((string) $event->request->input('email'), 100);

            AuditLogger::record('auth.lockout', "Sign-in locked after too many attempts for \"{$email}\"");
        });

        Event::listen(function (Login $event): void {
            if ($event->user instanceof User) {
                AuditLogger::record('auth.login', 'Logged in', userId: $event->user->id);
            }
        });

        Event::listen(function (Logout $event): void {
            if ($event->user instanceof User) {
                AuditLogger::record('auth.logout', 'Logged out', userId: $event->user->id);
            }
        });
    }

    /**
     * Record every backup run, manual or scheduled, as backup history.
     */
    protected function configureBackupAuditing(): void
    {
        Event::listen(fn (BackupWasSuccessful $event) => AuditLogger::record('backup.created', 'Backup completed'));

        Event::listen(fn (BackupHasFailed $event) => AuditLogger::record(
            'backup.failed',
            'Backup failed: '.Str::limit($event->exception->getMessage(), 250),
        ));
    }

    /**
     * Register every role permission as a Gate ability.
     */
    protected function configurePermissions(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasPermission($permission));
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
