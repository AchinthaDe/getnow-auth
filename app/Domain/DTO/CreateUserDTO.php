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
        public ?string $address = null,
        public ?string $mobile_phone = null,
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
            self::optionalString($data, 'address'),
            self::optionalString($data, 'mobile_phone'),
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

    /** @param array<string, mixed> $data */
    private static function optionalString(array $data, string $key): ?string
    {
        return isset($data[$key]) ? self::string($data, $key) : null;
    }

    /** @return array{name: string, email: string, username: string, password: string, address: string|null, mobile_phone: string|null} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'password' => $this->password,
            'address' => $this->address,
            'mobile_phone' => $this->mobile_phone,
        ];
    }
}
