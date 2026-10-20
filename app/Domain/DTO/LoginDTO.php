<?php

declare(strict_types=1);

namespace App\Domain\DTO;

final readonly class LoginDTO
{
    public function __construct(
        public string $identifier,
        public string $password,
    ) {}
}
