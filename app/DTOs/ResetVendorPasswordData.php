<?php

namespace App\DTOs;

readonly class ResetVendorPasswordData
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
        public ?int $vendorId = null,
    ) {}
}
