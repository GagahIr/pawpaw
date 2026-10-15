<?php

namespace App\DTOs;

readonly class RegisterClientData
{
    public function __construct(
        public string $name,
        public string $username,
        public ?string $phoneNumber,
        public string $email,
        public ?string $password,
        public ?string $googleId = null,
    ) {}
}
