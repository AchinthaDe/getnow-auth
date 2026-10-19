<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\DTO\CreateUserDTO;
use App\Domain\DTO\UpdateUserDTO;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserService
{
    public function __construct(private UserRepositoryInterface $users) {}

    /** @return LengthAwarePaginator<int, User> */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }

    public function get(int $id): User
    {
        return $this->users->findOrFail($id);
    }

    public function create(CreateUserDTO $data): User
    {
        return $this->users->create($data->toArray());
    }

    public function update(int $id, UpdateUserDTO $data): User
    {
        return $this->users->update($this->users->findOrFail($id), $data->toArray());
    }

    public function delete(int $id): void
    {
        $this->users->delete($this->users->findOrFail($id));
    }
}
