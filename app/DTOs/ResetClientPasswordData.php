<?php

namespace App\DTOs;

readonly class ResetClientPasswordData
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}
}
