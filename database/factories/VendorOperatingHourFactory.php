<?php

namespace Database\Factories;

use App\Models\VendorOperatingHour;
use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorOperatingHourFactory extends Factory
{
    protected $model = VendorOperatingHour::class;

    public function definition(): array
    {
        return [
            'day_of_week' => $this->faker->randomElement(DayOfWeek::cases()),
            'open_time' => '08:00:00',
            'close_time' => '20:00:00',
            'is_closed' => false,
        ];
    }
}
