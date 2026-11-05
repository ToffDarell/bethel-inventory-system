<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'asset_no' => 'BBH-' . str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'asset_name' => fake()->randomElement(['Hospital Bed', 'Patient Monitor', 'Wheelchair', 'Infusion Pump', 'Refrigerator', 'Desktop Computer', 'Split AC Unit']),
            'brand' => fake()->optional()->randomElement(['Philips', 'Mindray', 'GE Healthcare', 'Samsung', 'Panasonic']),
            'model' => fake()->bothify('Model-###??'),
            'serial_no' => fake()->optional()->bothify('SN-########'),
            'date_acquired' => fake()->optional()->date(),
            'department' => fake()->randomElement(['ER', 'ICU', 'Pharmacy', 'Nursing Station 3', 'Laboratory', 'Administration']),
            'section' => fake()->optional()->randomElement(['Station 3', 'Hematology', 'Cashier', 'Triage', 'Bay 2']),
            'assigned_personnel' => fake()->optional()->name(),
            'location' => fake()->randomElement(['ND-Station 3 Room 39', 'ER Bay 1', 'ICU Room 2', 'Main Pharmacy']),
            'status' => fake()->randomElement(Asset::STATUSES),
            'asset_condition' => fake()->optional()->randomElement(Asset::CONDITIONS),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
