<?php

namespace App\Providers;

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