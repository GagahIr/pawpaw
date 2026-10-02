<?php

namespace Database\Seeders;

use App\Models\CategoryService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Grooming',
            'Pet Hotel',
            'Clinic'
        ];

        foreach ($categories as $category) {
            CategoryService::firstOrCreate(['name' => $category]);
        }
    }
}
