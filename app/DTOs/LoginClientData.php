<?php

namespace App\DTOs;

readonly class LoginClientData
{
    public function __construct(
        public string $identifier,
        public string $password,
        public bool $remember = false,
    ) {}
}
