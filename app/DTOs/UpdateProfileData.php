<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

readonly class UpdateProfileData
{
    public function __construct(
        public string $name,
        public ?string $username,
        public string $email,
        public ?string $phoneNumber,
        public string $emailChangePassword = '',
        public ?UploadedFile $avatar = null,
        public ?int $vendorId = null,
    ) {}

    /** @return array{name: string, username: ?string, phone_number: ?string} */
    public function profileAttributes(): array
    {
        $attributes = ['name' => $this->name, 'username' => $this->username, 'phone_number' => $this->phoneNumber];

        return $attributes;
    }
}
