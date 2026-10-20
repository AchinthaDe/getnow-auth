<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Contracts\TokenIssuer;
use App\Domain\Accounts\Data\AuthResultData;
use App\Domain\DTO\CreateUserDTO;
use App\Domain\DTO\LoginDTO;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TokenIssuer $tokens,
    ) {}

    public function login(LoginDTO $data): AuthResultData
    {
        $user = $this->users->findByIdentifier($data->identifier);

        if ($user === null || ! Hash::check($data->password, $user->password) || $user->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }

        return new AuthResultData($user, $this->tokens->issue($user));
    }

    public function register(CreateUserDTO $data): AuthResultData
    {
        // A failed token handoff must not leave a partially registered account.
        return DB::transaction(function () use ($data): AuthResultData {
            $user = $this->users->create($data->toArray());

            return new AuthResultData($user, $this->tokens->issue($user));
        });
    }
}
