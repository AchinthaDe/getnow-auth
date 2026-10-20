<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\ValidatesRegistration;
use App\Domain\DTO\CreateUserDTO;
use App\Domain\User\Services\UserService;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use ValidatesRegistration;

    public function __construct(private UserService $users) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input  Unvalidated registration request data.
     */
    public function create(array $input): User
    {
        $validated = $this->validateRegistration($input);

        return $this->users->create(CreateUserDTO::fromArray($validated));
    }
}
