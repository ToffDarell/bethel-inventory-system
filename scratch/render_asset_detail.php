<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = \App\Models\User::where('role', 'admin')->first();
if (!$admin) {
    $admin = new \App\Models\User([
        'id' => 1,
        'name' => 'System Administrator',
        'username' => 'admin',
        'role' => 'admin',
        'must_change_password' => false,
    ]);
}
\Illuminate\Support\Facades\Auth::setUser($admin);
view()->share('errors', new \Illuminate\Support\ViewErrorBag());

// Find or build an asset with full data
$category = \App\Models\Category::firstOrCreate(
    ['name' => 'Ultrasound Systems'],
    ['default_useful_life_years' => 7]
);

$asset = \App\Models\Asset::where('asset_no', 'BBH-CARD-US-2024-0012')->first();
if (!$asset) {
    $asset = new \App\Models\Asset([
        'asset_no' => 'BBH-CARD-US-2024-0012',
        'asset_name' => 'Philips EPIQ 7 Diagnostic Ultrasound System',
        'brand' => 'Philips Healthcare',
        'model' => 'EPIQ 7 Premium Clinical Ultrasound',
        'serial_no' => 'US-PH-7291048-X',
        'date_acquired' => \Carbon\Carbon::now()->subMonths(26)->startOfMonth(),
        'acquired_year_only' => false,
        'category_id' => $category->id,
        'department' => 'Cardiology',
        'section' => 'Echo Laboratory',
        'location' => 'Cardio Diagnostics Room 204',
        'assigned_personnel' => 'Dr. Maria Santos, MD (Cardiologist)',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 1850000.00,
        'salvage_value' => 185000.00,
        'useful_life_years' => 7,
        'notes' => 'Includes X5-1 matrix transducer and pediatric cardiac phased probe. Calibrated on last quarter PM schedule.',
    ]);
    $asset->id = 101;
    $asset->created_at = \Carbon\Carbon::now()->subMonths(26);
    $asset->updated_at = \Carbon\Carbon::now()->subDays(3);
}

$asset->setRelation('category', $category);
$asset->setRelation('transfers', collect([
    new \App\Models\Transfer([
        'id' => 1,
        'asset_id' => 101,
        'from_department' => 'Biomedical Engineering',
        'to_department' => 'Cardiology',
        'from_location' => 'Main Receiving Bay',
        'to_location' => 'Cardio Diagnostics Room 204',
        'from_assigned_personnel' => 'Engr. J. Mercado',
        'to_assigned_personnel' => 'Dr. Maria Santos, MD',
        'moved_at' => \Carbon\Carbon::now()->subMonths(25),
        'reason' => 'Commissioning and initial clinical deployment',
        'mover_id' => $admin->id,
    ]),
]));

$depreciationService = app(\App\Services\DepreciationService::class);
$depreciation = $depreciationService->calculateForAsset($asset);

$html = view('assets.show', [
    'asset' => $asset,
    'depreciation' => $depreciation,
])->render();

// Ensure vite asset references are absolute or point to build directory
$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $cssFile = $manifest['resources/css/app.css']['file'] ?? '';
    $jsFile = $manifest['resources/js/app.js']['file'] ?? '';
    if ($cssFile) {
        $html = preg_replace('/<link rel="stylesheet" href="[^"]*app-[^"]*\.css"/', '<link rel="stylesheet" href="/build/' . $cssFile . '"', $html);
    }
}

file_put_contents(public_path('asset-show-preview.html'), $html);
echo "Wrote asset-show-preview.html successfully (" . strlen($html) . " bytes)\n";
