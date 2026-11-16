<?php

namespace Database\Seeders;

use App\Models\AssetTemplate;
use App\Models\Category;
use Illuminate\Database\Seeder;

class AssetTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed on fresh install or when the table is completely empty
        if (AssetTemplate::count() > 0) {
            return;
        }

        $categories = Category::all()->mapWithKeys(
            fn (Category $cat) => [mb_strtolower(trim($cat->name), 'UTF-8') => $cat->id]
        );

        $rows = [
            [
                'name' => 'Patient Monitor',
                'brand' => 'Mindray',
                'model' => 'uMEC10',
                'category' => 'Medical Equipment',
                'default_remarks' => null,
            ],
            [
                'name' => 'Defibrillator',
                'brand' => 'Philips',
                'model' => 'HeartStart XL',
                'category' => 'Medical Equipment',
                'default_remarks' => null,
            ],
            [
                'name' => 'Blood Pressure Set',
                'brand' => 'Welch Allyn',
                'model' => 'Adult Cuff',
                'category' => 'Medical Equipment',
                'default_remarks' => null,
            ],
            [
                'name' => 'Desktop Computer Set',
                'brand' => 'Dell',
                'model' => 'OptiPlex',
                'category' => 'IT Equipment',
                'default_remarks' => null,
            ],
            [
                'name' => 'Automatic Voltage Regulator',
                'brand' => 'Secure',
                'model' => '500W AVR',
                'category' => 'IT Equipment',
                'default_remarks' => null,
            ],
            [
                'name' => 'Split-Type Air Conditioner',
                'brand' => 'Koppel',
                'model' => '2.5HP Inverter',
                'category' => 'Appliances',
                'default_remarks' => null,
            ],
            [
                'name' => 'Refrigerator',
                'brand' => 'Samsung',
                'model' => 'Mini',
                'category' => 'Appliances',
                'default_remarks' => null,
            ],
            [
                'name' => 'Counter Table',
                'brand' => 'Local',
                'model' => 'Standard Wood',
                'category' => 'Furniture',
                'default_remarks' => null,
            ],
        ];

        foreach ($rows as $row) {
            $categoryId = $categories[mb_strtolower($row['category'], 'UTF-8')] ?? null;
            $name = AssetTemplate::normalizeRequired($row['name']);
            $brand = AssetTemplate::normalizeNullable($row['brand']);
            $model = AssetTemplate::normalizeNullable($row['model']);

            AssetTemplate::create([
                'name' => $name,
                'brand' => $brand,
                'model' => $model,
                'category_id' => $categoryId,
                'default_remarks' => $row['default_remarks'],
                'unique_key' => AssetTemplate::buildUniqueKey($name, $brand, $model),
                'active' => true,
            ]);
        }
    }
}
