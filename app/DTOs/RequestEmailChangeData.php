<?php

namespace App\DTOs;

readonly class RequestEmailChangeData
{
    public function __construct(public string $email, public string $password, public ?int $vendorId = null) {}
}
