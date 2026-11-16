<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;

test('printable inventory reports render all 4 grouped views for admin with purchase and current values and hide money for staff', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $category = Category::create([
        'name' => 'FURNITURE AND FIXTURES',
        'code' => 'FF',
        'useful_life_years' => 10,
        'salvage_percentage' => 10,
    ]);

    $vendor = Vendor::create([
        'name' => 'CITI HARDWARE',
        'active' => true,
        'is_donation' => false,
    ]);

    Asset::create([
        'asset_no' => '0000000130',
        'asset_name' => 'BLENDER-OSTER',
        'brand' => 'Oster',
        'model' => 'BL-100',
        'serial_no' => 'BBH-FF-014',
        'department' => 'ADMIN',
        'section' => 'DIETARY/KITCHEN',
        'category_id' => $category->id,
        'vendor_id' => $vendor->id,
        'date_acquired' => '2020-03-10',
        'cost' => 3699.00,
        'status' => 'Active',
        'asset_condition' => 'In Good Condition',
    ]);

    // 1. Admin views Asset by Category print sheet
    $this->actingAs($admin)
        ->get(route('reports.print', ['type' => 'category']))
        ->assertOk()
        ->assertSee('ASSET BY CATEGORY LIST')
        ->assertSee('ASSET CATEGORY : FURNITURE AND FIXTURES')
        ->assertSee('DEPARTMENT : ADMIN - DIETARY/KITCHEN')
        ->assertSee('0000000130')
        ->assertSee('BLENDER-OSTER')
        ->assertSee('Asset Count : 1')
        ->assertSee('SUB-TOTAL')
        ->assertSee('3,699.00');

    // 2. Admin views Asset by Date Acquired, Department, and Vendor
    $this->actingAs($admin)
        ->get(route('reports.print', ['type' => 'date_acquired']))
        ->assertOk()
        ->assertSee('ASSET BY DATE ACQUIRED LIST')
        ->assertSee('YEAR ACQUIRED : 2020');

    $this->actingAs($admin)
        ->get(route('reports.print', ['type' => 'department']))
        ->assertOk()
        ->assertSee('ASSET BY DEPARTMENT LIST')
        ->assertSee('DEPARTMENT : ADMIN - DIETARY/KITCHEN');

    $this->actingAs($admin)
        ->get(route('reports.print', ['type' => 'vendor']))
        ->assertOk()
        ->assertSee('ASSET BY VENDOR LIST')
        ->assertSee('VENDOR / SUPPLIER : CITI HARDWARE');

    // 3. Staff views Asset by Category print sheet: sees item & counts, never sees Purchase/Current or money
    $staffResp = $this->actingAs($staff)
        ->get(route('reports.print', ['type' => 'category']))
        ->assertOk()
        ->assertSee('ASSET BY CATEGORY LIST')
        ->assertSee('ASSET CATEGORY : FURNITURE AND FIXTURES')
        ->assertSee('DEPARTMENT : ADMIN - DIETARY/KITCHEN')
        ->assertSee('0000000130')
        ->assertSee('BLENDER-OSTER')
        ->assertSee('Asset Count : 1')
        ->assertDontSee('3,699.00')
        ->assertDontSee('SUB-TOTAL')
        ->assertDontSee('₱');
});
