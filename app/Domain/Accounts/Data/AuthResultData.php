<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Data;

use App\Models\User;

final readonly class AuthResultData
{
    public function __construct(
        public User $user,
        public TokenPairData $tokens,
    ) {}

    /** @return array{user: array{id: int, name: string, email: string, username: string}, tokens: array<string, mixed>} */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'username' => $this->user->username,
            ],
            'tokens' => $this->tokens->toArray(),
        ];
    }
}
