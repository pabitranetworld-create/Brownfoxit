<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

// GitHub auto-deploy hook (see .github/workflows/deploy.yml). Protected by DEPLOY_TOKEN.
Route::post('/_deploy', DeployController::class)->name('deploy.hook');

Route::post('/forms/{type}', [\App\Http\Controllers\FormController::class, 'submit'])->where('type', 'contact|quote')->name('forms.submit');
Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

/*
|--------------------------------------------------------------------------
| Admin panel  (/admin)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->name('login.attempt');
    });

    Route::middleware('admin')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::post('clear-cache', [Admin\DashboardController::class, 'clearCache'])->name('clear-cache');

        Route::get('profile', [Admin\ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

        // Home page blocks: /admin/settings/hero, /admin/settings/about …
        Route::get('settings/{section}', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings/{section}', [Admin\SettingsController::class, 'update'])->name('settings.update');

        // Pages + page builder
        Route::get('pages', [Admin\PageController::class, 'index'])->name('pages.index');
        Route::post('pages', [Admin\PageController::class, 'store'])->name('pages.store');
        Route::post('client-log', [Admin\PageController::class, 'clientLog'])->name('client-log');
        Route::prefix('pages/{page}')->name('pages.')->controller(Admin\PageController::class)->group(function () {
            Route::get('builder', 'builder')->name('builder');
            Route::post('save', 'save')->name('save');
            Route::post('publish', 'publish')->name('publish');
            Route::post('unpublish', 'unpublish')->name('unpublish');
            Route::post('duplicate', 'duplicate')->name('duplicate');
            Route::delete('/', 'destroy')->name('destroy');
            Route::match(['get', 'post'], 'preview', 'preview')->name('preview');
            Route::get('revisions', 'revisions')->name('revisions');
            Route::post('revisions/{revision}/restore', 'restore')->name('restore');
        });

        // Media library
        Route::get('messages', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
        Route::put('messages/{message}', [Admin\MessageController::class, 'update'])->name('messages.update');
        Route::delete('messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('settings/mail/test', [Admin\SettingsController::class, 'testMail'])->name('settings.mail-test');

        Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
        Route::get('media/list', [Admin\MediaController::class, 'list'])->name('media.list');
        Route::post('media', [Admin\MediaController::class, 'store'])->name('media.store');
        Route::post('media/sync', [Admin\MediaController::class, 'sync'])->name('media.sync');
        Route::put('media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');

        // Content tables: /admin/content/services, /admin/content/posts …
        Route::prefix('content/{resource}')->name('resource.')->controller(Admin\ResourceController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Builder pages: /about, /contact … (keep this LAST)
|--------------------------------------------------------------------------
*/
Route::get('/{slug}', [PageController::class, 'show'])->where('slug', \App\Models\Page::SLUG_PATTERN)->name('page');
