<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Accounts\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        return response()->json(['data' => $auth->login($request->toDTO())->toArray()]);
    }

    public function register(RegisterRequest $request, AuthService $auth): JsonResponse
    {
        return response()->json(['data' => $auth->register($request->toDTO())->toArray()], 201);
    }
}
