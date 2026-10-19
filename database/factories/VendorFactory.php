<?php

namespace Database\Factories;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'address' => fake()->address(),
            'contact' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'description' => fake()->sentence(),
            'latitude' => '-7.983908', // Contoh latitude statis
            'longitude' => '112.621391',
            'status_verification' => VendorVerificationStatus::Pending,
            'status_subscription' => VendorSubscriptionStatus::Inactive,
            'join_at' => now(),
        ];
    }
}
