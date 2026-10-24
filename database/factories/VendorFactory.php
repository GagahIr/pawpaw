<?php

namespace Database\Factories;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => $this->faker->address(),
            'contact' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'description' => $this->faker->paragraph(),
            'logo' => null,
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'status_verification' => VendorVerificationStatus::Pending,
            'status_subscription' => VendorSubscriptionStatus::Inactive,
            'join_at' => now(),
        ];
    }
}
