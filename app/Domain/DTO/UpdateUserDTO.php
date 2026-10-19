<?php

declare(strict_types=1);

namespace App\Domain\DTO;

final readonly class UpdateUserDTO
{
    /** @param array{name?: string, email?: string, username?: string, password?: string} $attributes */
    private function __construct(private array $attributes) {}

    /** @param array{name?: string, email?: string, username?: string, password?: string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /** @return array{name?: string, email?: string, username?: string, password?: string} */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
