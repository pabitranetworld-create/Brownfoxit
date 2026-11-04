<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
        // Older MySQL / MariaDB on shared hosting limits index length with utf8mb4.
        Schema::defaultStringLength(191);

        // Catch N+1 queries while developing.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
