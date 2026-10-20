<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): User;

    public function findByIdentifier(string $identifier): ?User;

    /** @param array{name: string, email: string, username: string, password: string, address?: string|null, mobile_phone?: string|null} $attributes */
    public function create(array $attributes): User;

    /** @param array{name?: string, email?: string, username?: string, password?: string} $attributes */
    public function update(User $user, array $attributes): User;

    public function delete(User $user): void;
}
