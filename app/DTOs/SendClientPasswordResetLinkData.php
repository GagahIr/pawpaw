<?php

namespace App\DTOs;

readonly class SendClientPasswordResetLinkData
{
    public function __construct(public string $email) {}
}
