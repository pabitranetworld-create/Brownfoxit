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

/* ---- Shop ---- */
Route::controller(\App\Http\Controllers\Shop\ShopController::class)->group(function () {
    Route::get('/shop', 'index')->name('shop.index');
    Route::get('/shop/category/{slug}', 'category')->name('shop.category');
    Route::get('/shop/{slug}', 'show')->name('shop.product');
});
Route::controller(\App\Http\Controllers\Shop\CartController::class)->group(function () {
    Route::get('/cart', 'show')->name('cart.show');
    Route::post('/cart/add', 'add')->name('cart.add');
    Route::post('/cart/update', 'update')->name('cart.update');
    Route::post('/cart/remove/{item}', 'remove')->name('cart.remove');
    Route::post('/cart/coupon', 'coupon')->name('cart.coupon');
});
Route::controller(\App\Http\Controllers\Shop\CheckoutController::class)->group(function () {
    Route::get('/checkout', 'show')->name('checkout.show');
    Route::post('/checkout', 'place')->middleware('throttle:20,1')->name('checkout.place');
    Route::get('/order/{number}', 'order')->name('shop.order');
    Route::post('/order/{number}/pay', 'pay')->middleware('throttle:20,1')->name('shop.order.pay');
});
Route::match(['get', 'post'], '/payment/{gateway}/return/{ref}/{result}', [\App\Http\Controllers\Shop\PaymentController::class, 'return'])->where('result', 'success|fail|cancel')->name('payment.return');
Route::match(['get', 'post'], '/payment/{gateway}/ipn', [\App\Http\Controllers\Shop\PaymentController::class, 'ipn'])->name('payment.ipn');
Route::get('/download/{token}', [\App\Http\Controllers\Shop\DownloadController::class, 'download'])->middleware('throttle:30,1')->name('shop.download');

/* ---- Customer account ---- */
Route::prefix('account')->name('account.')->controller(\App\Http\Controllers\Shop\AccountController::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', 'loginForm')->name('login');
        Route::post('login', 'login')->middleware('throttle:10,1');
        Route::get('register', 'registerForm')->name('register');
        Route::post('register', 'register')->middleware('throttle:10,1');
        Route::get('forgot-password', 'forgotForm')->name('forgot');
        Route::post('forgot-password', 'forgot')->middleware('throttle:5,1');
        Route::get('reset-password/{token}', 'resetForm')->name('reset');
        Route::post('reset-password', 'reset')->name('reset.save');
    });
    Route::middleware('auth')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('orders', 'orders')->name('orders');
        Route::get('downloads', 'downloads')->name('downloads');
        Route::get('profile', 'profile')->name('profile');
        Route::put('profile', 'saveProfile')->name('profile.save');
        Route::post('logout', 'logout')->name('logout');
    });
});

/* ---- Portfolio & blog ---- */
Route::get('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{slug}', [\App\Http\Controllers\PortfolioController::class, 'show'])->name('portfolio.show');
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [\App\Http\Controllers\BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

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

        // Store
        Route::prefix('orders')->name('orders.')->controller(Admin\OrderController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{order}', 'show')->whereNumber('order')->name('show');
            Route::put('{order}', 'update')->whereNumber('order')->name('update');
            Route::post('{order}/confirm', 'confirmPayment')->whereNumber('order')->name('confirm');
            Route::post('{order}/recheck', 'recheck')->whereNumber('order')->name('recheck');
            Route::post('{order}/resend', 'resend')->whereNumber('order')->name('resend');
            Route::post('{order}/download/{download}', 'download')->whereNumber('order')->name('download');
            Route::post('{order}/grant', 'grant')->whereNumber('order')->name('grant');
        });
        Route::get('customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::prefix('payments')->name('payments.')->controller(Admin\PaymentSettingsController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{gateway}', 'edit')->where('gateway', '[a-z0-9_]+')->name('edit');
            Route::put('{gateway}', 'update')->where('gateway', '[a-z0-9_]+')->name('update');
            Route::post('{gateway}/toggle', 'toggle')->where('gateway', '[a-z0-9_]+')->name('toggle');
            Route::post('{gateway}/test', 'test')->where('gateway', '[a-z0-9_]+')->name('test');
        });

        // WordPress import (Phase 3: portfolio + blog migration)
        Route::get('import', [Admin\ImportController::class, 'index'])->name('import.index');
        Route::post('import/discover', [Admin\ImportController::class, 'discover'])->name('import.discover');
        Route::post('import/run', [Admin\ImportController::class, 'run'])->name('import.run');

        // Content tables: /admin/content/services, /admin/content/posts …
        Route::prefix('content/{resource}')->name('resource.')->controller(Admin\ResourceController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy');
            Route::post('{id}/duplicate', 'duplicate')->whereNumber('id')->name('duplicate');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Builder pages: /about, /contact … (keep this LAST)
|--------------------------------------------------------------------------
*/
Route::get('/{slug}', [PageController::class, 'show'])->where('slug', \App\Models\Page::SLUG_PATTERN)->name('page');
