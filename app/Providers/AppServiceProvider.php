<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register component installation services
        $this->app->singleton(\App\Services\SecurePackageInstaller::class);
        $this->app->singleton(\App\Services\ServiceProviderDetector::class);
        $this->app->singleton(\App\Services\ComponentInstallationService::class);
    }
}
