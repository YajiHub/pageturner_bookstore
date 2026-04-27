<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

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
        // Lab 6: Tiered request governance with short-window burst control.
        RateLimiter::for('public-read', function (Request $request) {
            return [
                Limit::perSecond(5)->by($request->ip()),
                Limit::perMinute(180)->by($request->ip()),
            ];
        });

        RateLimiter::for('customer-actions', function (Request $request) {
            $identity = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perSecond(3)->by($identity),
                Limit::perMinute(120)->by($identity),
            ];
        });

        RateLimiter::for('admin-actions', function (Request $request) {
            $identity = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perSecond(8)->by($identity),
                Limit::perMinute(360)->by($identity),
            ];
        });

        RateLimiter::for('critical-write', function (Request $request) {
            $identity = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perSecond(1)->by($identity),
                Limit::perMinute(30)->by($identity),
            ];
        });

        Event::listen(Login::class, function (Login $event) {
            AuditLogger::log(
                action: 'auth.login',
                auditable: $event->user,
                description: 'User logged in.',
                userId: $event->user->id
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (! $event->user) {
                return;
            }

            AuditLogger::log(
                action: 'auth.logout',
                auditable: $event->user,
                description: 'User logged out.',
                userId: $event->user->id
            );
        });

        Event::listen(Registered::class, function (Registered $event) {
            AuditLogger::log(
                action: 'auth.registered',
                auditable: $event->user,
                newValues: [
                    'email' => $event->user->email,
                    'role' => $event->user->role,
                ],
                description: 'New account registered.',
                userId: $event->user->id
            );
        });

        Event::listen(Verified::class, function (Verified $event) {
            AuditLogger::log(
                action: 'auth.email_verified',
                auditable: $event->user,
                description: 'Email verification completed.',
                userId: $event->user->id
            );
        });
    }
}
