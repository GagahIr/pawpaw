<?php

namespace App\DTOs;

readonly class RegisterVendorData
{

    public function __construct(
        public string $vendorName,
        public string $address,
        public string $contact,
        public string $emailVendor,
        public string $email,
        public string $description,
        public string $ownerName,
        public string $phoneNumber,
        public string $password,
    ) {}
}
