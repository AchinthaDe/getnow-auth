<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\DTO\CreateUserDTO;
use App\Domain\DTO\UpdateUserDTO;
use App\Domain\User\Services\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserController extends Controller
{
    public function __construct(private UserService $users) {}

    public function index(): JsonResponse
    {
        return response()->json($this->users->list());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return response()->json([
            'data' => $this->users->create(CreateUserDTO::fromArray($validated)),
        ], 201);
    }

    public function show(int $user): JsonResponse
    {
        return response()->json(['data' => $this->users->get($user)]);
    }

    public function update(Request $request, int $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
        ]);

        return response()->json([
            'data' => $this->users->update($user, UpdateUserDTO::fromArray($validated)),
        ]);
    }

    public function destroy(int $user): JsonResponse
    {
        $this->users->delete($user);

        return response()->json(status: 204);
    }
}
