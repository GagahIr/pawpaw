<?php

namespace App\DTOs;

readonly class GoogleClientData
{
    public function __construct(
        public string $googleId,
        public string $email,
        public bool $emailVerified,
        public ?string $hostedDomain = null,
        public bool $remember = false,
        public string $name = '',
    ) {}
}
