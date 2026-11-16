<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    Setting::set('depreciation_method', 'straight_line');
    Setting::set('depreciation_convention', 'actual_date');
    Setting::set('depreciation_min_cost', '0');
});

test('admin sees compact depreciation card with all metrics when asset is calculable', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::create([
        'name' => 'Biomedical Equipment ' . uniqid(),
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10,
    ]);

    $acquiredDate = Carbon::now()->subMonths(24)->startOfMonth();

    $asset = Asset::factory()->create([
        'category_id' => $category->id,
        'cost' => 120000.00,
        'salvage_value' => 12000.00,
        'useful_life_years' => 5,
        'date_acquired' => $acquiredDate,
        'acquired_year_only' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('assets.show', $asset));

    $response->assertOk();
    $response->assertSee('Depreciation & Valuation');
    $response->assertSee('Year Bought');
    $response->assertSee($acquiredDate->format('Y'));
    $response->assertSee('Cost');
    $response->assertSee('₱120,000.00');
    $response->assertSee('Accumulated');
    $response->assertSee('Current value');
    $response->assertSee('months elapsed');
    $response->assertSee('of 60 months elapsed');
    $response->assertSee('Expected end date:');
    $response->assertSee('Disposal value floor:');
    $response->assertSee('₱12,000.00');
});

test('admin sees "Select a category and enter a cost to calculate" state when asset has null cost', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::create([
        'name' => 'Diagnostic Devices ' . uniqid(),
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10,
    ]);

    $asset = Asset::factory()->create([
        'category_id' => $category->id,
        'cost' => null,
        'useful_life_years' => 5,
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    $response = $this->actingAs($admin)->get(route('assets.show', $asset));

    $response->assertOk();
    $response->assertSee('Depreciation & Valuation');
    $response->assertSee('Select a category and enter a cost to calculate');
    $response->assertSee('Edit Asset Details');
    $response->assertDontSee('months elapsed');
});

test('admin sees "Select a category and enter a cost to calculate" state when asset is missing category', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $asset = Asset::factory()->create([
        'category_id' => null,
        'cost' => 50000.00,
        'useful_life_years' => null,
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    $response = $this->actingAs($admin)->get(route('assets.show', $asset));

    $response->assertOk();
    $response->assertSee('Depreciation & Valuation');
    $response->assertSee('Select a category and enter a cost to calculate');
    $response->assertSee('Edit Asset Details');
    $response->assertDontSee('months elapsed');
});

test('staff user NEVER sees depreciation card, container, or any money/₱ figures in asset detail view', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $category = Category::create([
        'name' => 'Clinical Monitoring ' . uniqid(),
        'default_useful_life_years' => 5,
    ]);

    $asset = Asset::factory()->create([
        'category_id' => $category->id,
        'cost' => 850000.00,
        'salvage_value' => 85000.00,
        'useful_life_years' => 5,
        'date_acquired' => Carbon::now()->subYears(2),
    ]);

    $response = $this->actingAs($staff)->get(route('assets.show', $asset));

    $response->assertOk();
    $response->assertDontSee('Depreciation & Valuation');
    $response->assertDontSee('No cost entered');
    $response->assertDontSee('Not enough data');
    $response->assertDontSee('Current Value');
    $response->assertDontSee('Accumulated');
    $response->assertDontSee('₱');
    $response->assertDontSee('850,000');
    $response->assertDontSee('85,000');
});

test('asset detail blade view contains no em dashes in text', function () {
    $content = file_get_contents(resource_path('views/assets/show.blade.php'));

    expect($content)->not->toContain('—');
    expect($content)->not->toContain('–');
});
