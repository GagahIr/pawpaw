<?php

namespace Database\Factories;

use App\Models\VendorService;
use App\Models\CategoryService;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorServiceFactory extends Factory
{
    protected $model = VendorService::class;

    public function definition(): array
    {
        return [
            // vendor_id diisi dari Seeder
            'category_service_id' => CategoryService::inRandomOrder()->first()->id ?? CategoryService::factory(),
            'status_service' => $this->faker->boolean(),
        ];
    }
}
