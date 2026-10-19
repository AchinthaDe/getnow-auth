<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use InvalidArgumentException;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $username,
        public string $password,
    ) {}

    /**
     * Build the DTO from validated input.
     *
     * The validator only guarantees an array, so the keys and value types are
     * enforced here before the DTO can be constructed.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::string($data, 'name'),
            self::string($data, 'email'),
            self::string($data, 'username'),
            self::string($data, 'password'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf('Validation is missing a string value for "%s".', $key));
        }

        return $value;
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
