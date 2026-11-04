<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
});

test('category seeder creates default hospital categories without overwriting existing entries', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(7);
    expect(Category::where('name', 'Medical Equipment')->first()->default_useful_life_years)->toBe(10);
    expect(Category::where('name', 'IT Equipment')->first()->default_useful_life_years)->toBe(5);
    expect(Category::where('name', 'Furniture')->first()->default_useful_life_years)->toBe(10);
    expect(Category::where('name', 'Appliances')->first()->default_useful_life_years)->toBe(8);
    expect(Category::where('name', 'Vehicles')->first()->default_useful_life_years)->toBe(7);
    expect(Category::where('name', 'Building Fixtures')->first()->default_useful_life_years)->toBe(15);
    expect(Category::where('name', 'Other')->first()->default_useful_life_years)->toBeNull();

    // Re-run seeder to ensure idempotency
    $this->seed(CategorySeeder::class);
    expect(Category::count())->toBe(7);
});

test('asset supports optional category and financial attributes', function () {
    $category = Category::create([
        'name' => 'Biomedical Devices',
        'default_useful_life_years' => 8,
        'default_salvage_percent' => 5.0,
    ]);

    $asset = Asset::create([
        'asset_no' => 'BBH-CAT-001',
        'asset_name' => 'Infusion Pump',
        'status' => 'In Use',
        'category_id' => $category->id,
        'cost' => 85000.50,
        'useful_life_years' => 8,
        'salvage_value' => 4250.00,
    ]);

    expect($asset->category->name)->toBe('Biomedical Devices');
    expect((float) $asset->cost)->toBe(85000.50);
    expect($asset->useful_life_years)->toBe(8);
    expect((float) $asset->salvage_value)->toBe(4250.00);
});
