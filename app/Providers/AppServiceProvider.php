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
        // One SEO object per request: views tell it what the page is, the layout prints the tags once.
        $this->app->scoped(\App\Seo\Seo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Older MySQL / MariaDB on shared hosting limits index length with utf8mb4.
        Schema::defaultStringLength(191);

        // A site configured for https always links its CSS, JS and pages over https
        // (otherwise a proxy in front of the server can make browsers block them as "mixed content").
        if (str_starts_with((string) config('app.url'), 'https://') && ! $this->app->runningInConsole()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // "Connect with Google" mail transport (Gmail API, send-only).
        \Illuminate\Support\Facades\Mail::extend('gmail-api', fn () => new \App\Mail\GmailApiTransport);

        // Sitemap refresh + automatic 301 redirects when a URL changes.
        foreach ([\App\Models\Page::class, \App\Models\Post::class, \App\Models\PostCategory::class, \App\Models\Project::class, \App\Models\Product::class, \App\Models\ProductCategory::class] as $model) {
            $model::observe(\App\Seo\ContentObserver::class);
        }

        // Catch N+1 queries while developing.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
