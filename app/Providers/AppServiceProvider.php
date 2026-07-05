<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

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
        Paginator::defaultView('vendor.pagination.table');
        Paginator::defaultSimpleView('vendor.pagination.simple-table');

        // Force HTTPS whenever APP_URL is https — works regardless of APP_ENV value.
        // Also trust Railway's load balancer proxy headers (X-Forwarded-Proto etc.)
        // so that request()->secure() and URL generation both return correct scheme.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
