<?php

use Illuminate\Support\Facades\Route;
Route::apiResource('projects', App\Http\Controllers\Api\ProjectController::class);