<?php

namespace Database\Factories;

use App\Models\VendorServiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorServiceItemFactory extends Factory
{
    protected $model = VendorServiceItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->numberBetween(50000, 500000),
            'duration' => $this->faker->numberBetween(15, 120), // menit
            'capacity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
