<?php

use App\Models\Asset;
use App\Models\Category;
use App\Services\DepreciationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

test('additive migrations apply cleanly on an existing database with assets, preserving all old data', function () {
    $tempDbPath = database_path('test_migration_copy.sqlite');

    if (File::exists($tempDbPath)) {
        File::delete($tempDbPath);
    }
    touch($tempDbPath);

    config([
        'database.connections.migration_test' => [
            'driver' => 'sqlite',
            'database' => $tempDbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    // Create legacy schema directly
    $schema = DB::connection('migration_test')->getSchemaBuilder();

    $schema->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('role')->default('staff');
        $table->boolean('must_change_password')->default(false);
        $table->rememberToken();
        $table->timestamps();
    });

    $schema->create('assets', function ($table) {
        $table->id();
        $table->string('asset_no')->unique();
        $table->string('asset_name');
        $table->string('model')->nullable();
        $table->string('serial_no')->nullable();
        $table->date('date_acquired')->nullable();
        $table->string('department')->nullable();
        $table->string('location')->nullable();
        $table->string('status')->default('In Use');
        $table->text('notes')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    // Seed legacy asset records into the database
    $legacyAssets = [
        [
            'asset_no' => 'BBH-000101',
            'asset_name' => 'Legacy Hospital Bed',
            'model' => 'Hill-Rom 1000',
            'serial_no' => 'SN-BED-101',
            'date_acquired' => '2022-03-15',
            'department' => 'Ward 3',
            'location' => 'Room 302',
            'status' => 'In Use',
            'notes' => 'Legacy bed in good condition',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'asset_no' => 'BBH-000102',
            'asset_name' => 'Legacy Patient Monitor',
            'model' => 'Mindray BeneView',
            'serial_no' => 'SN-MON-991',
            'date_acquired' => '2021-08-10',
            'department' => 'ICU',
            'location' => 'Bed 4',
            'status' => 'In Use',
            'notes' => 'Legacy monitor with battery intact',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];

    DB::connection('migration_test')->table('assets')->insert($legacyAssets);
    expect(DB::connection('migration_test')->table('assets')->count())->toBe(2);

    $previousDefault = DB::getDefaultConnection();
    DB::setDefaultConnection('migration_test');

    try {
        // Run the 2026_11_04 migrations on top of this legacy database
        $catMigration = require database_path('migrations/2026_11_04_000001_create_categories_table.php');
        $catMigration->up();

        $assetMigration = require database_path('migrations/2026_11_04_000002_add_category_and_cost_to_assets_table.php');
        $assetMigration->up();

        // Verify legacy assets are unchanged and can be queried
        $postMigrationAssets = DB::connection('migration_test')->table('assets')->orderBy('asset_no')->get();
        expect($postMigrationAssets)->toHaveCount(2);

        $bed = $postMigrationAssets->firstWhere('asset_no', 'BBH-000101');
        expect($bed->asset_name)->toBe('Legacy Hospital Bed');
        expect($bed->model)->toBe('Hill-Rom 1000');
        expect($bed->serial_no)->toBe('SN-BED-101');
        expect($bed->date_acquired)->toBe('2022-03-15');
        expect($bed->department)->toBe('Ward 3');
        expect($bed->location)->toBe('Room 302');
        expect($bed->status)->toBe('In Use');
        expect($bed->notes)->toBe('Legacy bed in good condition');

        // New fields must be cleanly null
        expect($bed->category_id)->toBeNull();
        expect($bed->cost)->toBeNull();
        expect($bed->useful_life_years)->toBeNull();
        expect($bed->salvage_value)->toBeNull();

        // Verify DepreciationService returns "No cost entered" for legacy assets with null cost
        $service = new DepreciationService();
        $result = $service->calculate(
            $bed->cost,
            $bed->salvage_value,
            $bed->useful_life_years,
            $bed->date_acquired
        );

        expect($result->isCalculable())->toBeFalse();
        expect($result->getMissingReason())->toBe('No cost entered');
        expect($service->formatPeso($result->getCurrentValue()))->toBe('No cost entered');
    } finally {
        DB::setDefaultConnection($previousDefault);
        DB::disconnect('migration_test');
        if (File::exists($tempDbPath)) {
            File::delete($tempDbPath);
        }
    }
});
