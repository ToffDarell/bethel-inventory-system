<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Medical Equipment',
                'default_useful_life_years' => 10,
                'default_salvage_percent' => 0.00,
            ],
            [
                'name' => 'IT Equipment',
                'default_useful_life_years' => 5,
                'default_salvage_percent' => 0.00,
            ],
            [
                'name' => 'Furniture',
                'default_useful_life_years' => 10,
                'default_salvage_percent' => 0.00,
            ],
            [
                'name' => 'Appliances',
                'default_useful_life_years' => 8,
                'default_salvage_percent' => 0.00,
            ],
            [
                'name' => 'Vehicles',
                'default_useful_life_years' => 7,
                'default_salvage_percent' => 0.00,
            ],
            [
                'name' => 'Building Fixtures',
                'default_useful_life_years' => 15,
                'default_salvage_percent' => 0.00,
            ],
            [
                'name' => 'Other',
                'default_useful_life_years' => null,
                'default_salvage_percent' => 0.00,
            ],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
