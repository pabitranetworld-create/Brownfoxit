<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The deploy hook is called by GitHub Actions, not by a browser form.
        $middleware->validateCsrfTokens(except: ['_deploy', 'admin/client-log', 'payment/*']); // gateways POST back to /payment/…

        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*') ? route('admin.login') : route('account.login'));
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->isAdmin() && $request->is('admin', 'admin/*') ? route('admin.dashboard') : route('account.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Public 404: show the editable "page-not-found" builder page when it exists.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('admin', 'admin/*', '_deploy')) {
                return null;
            }

            return rescue(fn () => app(\App\Http\Controllers\PageController::class)->notFound(), null, false);
        });
    })->create();
