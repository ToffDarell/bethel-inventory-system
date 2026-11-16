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

$category = \App\Models\Category::firstOrCreate(
    ['name' => 'Ultrasound Systems'],
    ['default_useful_life_years' => 7]
);

// Asset with No Cost
$noCostAsset = new \App\Models\Asset([
    'asset_no' => 'BBH-NURS-PUMP-2025-0089',
    'asset_name' => 'Alaris Medley Infusion Pump Module (Dual Channel)',
    'brand' => 'BD CareFusion',
    'model' => 'Alaris 8100 Series Pump Module',
    'serial_no' => 'SN-BD-449102-PH',
    'date_acquired' => \Carbon\Carbon::now()->subMonths(8),
    'acquired_year_only' => false,
    'category_id' => $category->id,
    'department' => 'Pediatrics',
    'section' => 'NICU',
    'location' => 'Neonatal ICU Bed 4',
    'assigned_personnel' => 'Nurse Supervisor Reyes, RN',
    'status' => 'In Use',
    'asset_condition' => 'In Good Condition',
    'cost' => null,
    'salvage_value' => null,
    'useful_life_years' => 5,
    'notes' => 'Donated medical equipment, cost documentation pending from donor records.',
]);
$noCostAsset->id = 102;
$noCostAsset->created_at = \Carbon\Carbon::now()->subMonths(8);
$noCostAsset->updated_at = \Carbon\Carbon::now()->subDays(1);
$noCostAsset->setRelation('category', $category);
$noCostAsset->setRelation('transfers', collect([]));

$depreciationService = app(\App\Services\DepreciationService::class);
$depreciation = $depreciationService->calculateForAsset($noCostAsset);

$html = view('assets.show', [
    'asset' => $noCostAsset,
    'depreciation' => $depreciation,
])->render();

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $cssFile = $manifest['resources/css/app.css']['file'] ?? '';
    if ($cssFile) {
        $html = preg_replace('/<link rel="stylesheet" href="[^"]*app-[^"]*\.css"/', '<link rel="stylesheet" href="/build/' . $cssFile . '"', $html);
    }
}

file_put_contents(public_path('asset-nocost-preview.html'), $html);
echo "Wrote asset-nocost-preview.html\n";
