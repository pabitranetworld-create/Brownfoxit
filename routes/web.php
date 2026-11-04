<?php

use App\Http\Controllers\DeployController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
| New pages (About, Services, Portfolio, Shop, Blog, Contact) and the admin
| panel are added here step by step.
*/

Route::get('/', HomeController::class)->name('home');

// GitHub auto-deploy hook (see .github/workflows/deploy.yml). Protected by DEPLOY_TOKEN.
Route::post('/_deploy', DeployController::class)->name('deploy.hook');
