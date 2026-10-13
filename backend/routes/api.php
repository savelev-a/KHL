<?php

use App\Http\Controllers\Api\ProbabilityController;
use App\Http\Controllers\Api\SimulationController;
use App\Http\Controllers\Api\StandingController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/standings',           [StandingController::class, 'index']);
    Route::get('/teams/{id}',          [TeamController::class, 'show']);
    Route::get('/probabilities',       [ProbabilityController::class, 'index']);
    Route::get('/simulations/latest',  [SimulationController::class, 'latest']);
});