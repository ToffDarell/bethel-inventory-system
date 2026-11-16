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
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
            ],
            [
                'name' => 'IT Equipment',
                'default_useful_life_years' => 5,
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
            ],
            [
                'name' => 'Furniture',
                'default_useful_life_years' => 10,
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
            ],
            [
                'name' => 'Appliances',
                'default_useful_life_years' => 8,
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
            ],
            [
                'name' => 'Vehicles',
                'default_useful_life_years' => 7,
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
            ],
            [
                'name' => 'Building Fixtures',
                'default_useful_life_years' => 15,
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
            ],
            [
                'name' => 'Other',
                'default_useful_life_years' => null,
                'default_salvage_percent' => null,
                'disposal_reviewed' => false,
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
