<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

// GitHub auto-deploy hook (see .github/workflows/deploy.yml). Protected by DEPLOY_TOKEN.
Route::post('/_deploy', DeployController::class)->name('deploy.hook');

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
