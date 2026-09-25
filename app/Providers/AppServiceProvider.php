<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        Authenticate::redirectUsing(fn () => route('login'));
    }

    /**
     * Record logins and logouts in the audit trail.
     */
    protected function configureAuthAuditing(): void
    {
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
