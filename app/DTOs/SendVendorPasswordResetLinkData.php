<?php

namespace App\DTOs;

readonly class SendVendorPasswordResetLinkData
{
    public function __construct(
        public string $email,
        public ?int $vendorId = null,
    ) {}
}
