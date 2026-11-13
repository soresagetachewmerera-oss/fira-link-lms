<?php

use App\Http\Controllers\Setup\DatabaseController;
use App\Http\Controllers\Setup\EnvironmentController;
use App\Http\Controllers\Setup\FinalController;
use App\Http\Controllers\Setup\PermissionsController;
use App\Http\Controllers\Setup\RequirementsController;
use App\Http\Controllers\Setup\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('install')
    ->name('setup.')
    ->group(function () {
        Route::get('/', WelcomeController::class)->name('welcome');
        Route::get('/requirements', RequirementsController::class)->name('requirements');
        Route::get('/permissions', PermissionsController::class)->name('permissions');

        Route::get('/environment', [EnvironmentController::class, 'show'])->name('environment');
        Route::post('/environment', [EnvironmentController::class, 'store'])->name('environment.store');

        Route::get('/database', [DatabaseController::class, 'show'])->name('database');
        Route::post('/database', [DatabaseController::class, 'run'])->name('database.run');

        Route::get('/final', FinalController::class)->name('final');
    });

