<?php

namespace App\DTOs;

class ManageStaffData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone_number,
        public string $password,
        public string $position,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone_number: $data['phone_number'],
            password: $data['password'] ?? '',
            position: $data['position'],
        );
    }
}
