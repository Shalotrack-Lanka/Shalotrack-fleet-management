<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // Rate limits are keyed by the signed-in Firebase user, NOT by IP: behind
        // Cloudflare + ALB the client IP can be forged through X-Forwarded-For, which
        // would make an IP-keyed limit worthless. Falls back to IP for anonymous callers.
        $key = fn (Request $r) => ($r->hasSession() ? $r->session()->get('firebase_uid') : null)
            ? 'u:' . $r->session()->get('firebase_uid')
            : 'ip:' . $r->ip();

        // Blanket ceiling for every logged-in route (a page makes several calls).
        RateLimiter::for('portal', fn (Request $r) => Limit::perMinute(240)->by($key($r)));

        // Phone-number invites: an enumeration/spam target — slow and capped per day.
        RateLimiter::for('invite', fn (Request $r) => [
            Limit::perMinute(5)->by('invite-m:' . $key($r)),
            Limit::perDay(30)->by('invite-d:' . $key($r)),
        ]);

        // Vehicle-health polling: cheap (cached 15 s server-side) but still capped per user.
        RateLimiter::for('health', fn (Request $r) => Limit::perMinute(30)->by('health:' . $key($r)));

        // Reminder saves/deletes: tiny, human-speed actions — a low cap stops scripted spam.
        RateLimiter::for('reminders', fn (Request $r) => Limit::perMinute(20)->by('reminders:' . $key($r)));

        // Alert-settings saves: human-speed edits — a low cap stops scripted spam.
        RateLimiter::for('alert-settings', fn (Request $r) => Limit::perMinute(20)->by('alert-settings:' . $key($r)));

        // Force HTTPS for every generated URL (asset(), route(), redirects,
        // @vite tags) in production.
        //
        // Why this is required: Cloudflare terminates HTTPS, then talks to the
        // ALB over plain HTTP, so the ALB tells Laravel X-Forwarded-Proto=http.
        // Without this, Laravel generates http:// URLs on an https:// page and
        // the browser blocks the CSS/JS/fetch() calls as mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}