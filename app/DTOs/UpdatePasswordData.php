<?php

namespace App\DTOs;

readonly class UpdatePasswordData
{
    public function __construct(public string $currentPassword, public string $password, public string $confirmation) {}
}
