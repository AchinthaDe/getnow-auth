<?php

declare(strict_types=1);

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:registration');
