<?php

declare(strict_types=1);

namespace App\Domain\DTO;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $username,
        public string $password,
    ) {}

    /** @param array{name: string, email: string, username: string, password: string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['email'], $data['username'], $data['password']);
    }

    /** @return array{name: string, email: string, username: string, password: string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
