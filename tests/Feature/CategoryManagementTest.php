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

test('admin can create and update categories from settings', function () {
    $this->actingAs($this->admin)->post('/settings/categories', [
        'name' => 'Laboratory Equipment',
        'default_useful_life_years' => 12,
        'default_salvage_percent' => 10,
    ])->assertRedirect();

    $cat = Category::where('name', 'Laboratory Equipment')->first();
    expect($cat)->not->toBeNull();
    expect($cat->default_useful_life_years)->toBe(12);
    expect((float) $cat->default_salvage_percent)->toBe(10.0);

    $this->actingAs($this->admin)->put("/settings/categories/{$cat->id}", [
        'name' => 'Clinical Lab Equipment',
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 5,
    ])->assertRedirect();

    expect($cat->fresh()->name)->toBe('Clinical Lab Equipment');
    expect($cat->fresh()->default_useful_life_years)->toBe(10);
});

test('blocks category deletion when category is assigned to active or soft-deleted assets', function () {
    $cat = Category::create(['name' => 'Surgical Equipment']);

    $asset = Asset::create([
        'asset_no' => 'BBH-SURG-01',
        'asset_name' => 'Anesthesia Machine',
        'status' => 'In Use',
        'category_id' => $cat->id,
    ]);

    // Attempt delete while asset is active
    $this->actingAs($this->admin)->delete("/settings/categories/{$cat->id}")
        ->assertSessionHasErrors('category');
    expect(Category::find($cat->id))->not->toBeNull();

    // Soft-delete the asset
    $asset->delete();

    // Attempt delete while asset is soft-deleted
    $this->actingAs($this->admin)->delete("/settings/categories/{$cat->id}")
        ->assertSessionHasErrors('category');
    expect(Category::find($cat->id))->not->toBeNull();

    // An unassigned category CAN be deleted
    $unusedCat = Category::create(['name' => 'Temporary Category']);
    $this->actingAs($this->admin)->delete("/settings/categories/{$unusedCat->id}")
        ->assertRedirect();
    expect(Category::find($unusedCat->id))->toBeNull();
});

test('staff cannot create, edit, or delete categories', function () {
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $cat = Category::create(['name' => 'Office Supply']);

    $this->actingAs($staff)->post('/settings/categories', ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($staff)->put("/settings/categories/{$cat->id}", ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($staff)->delete("/settings/categories/{$cat->id}")->assertForbidden();
});

test('settings page displays categories tab with categories list for admin', function () {
    Category::create(['name' => 'Endoscopy Units', 'default_useful_life_years' => 6]);

    $response = $this->actingAs($this->admin)->get('/settings');
    $response->assertOk();
    $response->assertSee('Categories');
    $response->assertSee('Endoscopy Units');
});
