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

// Ensure items for attention list exist
$category = \App\Models\Category::firstOrCreate(['name' => 'Biomedical Equipment'], ['default_useful_life_years' => 5]);

// 1. Under repair
if (\App\Models\Asset::where('status', 'Under Repair')->count() === 0) {
    \App\Models\Asset::create([
        'asset_no' => 'BBH-REP-001',
        'asset_name' => 'Drager Evita V300 Critical Care Ventilator',
        'status' => 'Under Repair',
        'location' => 'Biomedical Engineering Workshop',
        'department' => 'Respiratory Care',
        'category_id' => $category->id,
    ]);
}

// 2. Damaged condition
if (\App\Models\Asset::where('asset_condition', 'Damage')->count() === 0) {
    \App\Models\Asset::create([
        'asset_no' => 'BBH-DMG-002',
        'asset_name' => 'Mindray BeneHeart D3 Defibrillator Unit',
        'status' => 'In Use',
        'asset_condition' => 'Damage',
        'location' => 'Emergency Room Bay 3',
        'department' => 'Emergency',
        'category_id' => $category->id,
    ]);
}

// 3. Unassigned location
if (\App\Models\Asset::whereNull('location')->orWhere('location', '')->count() === 0) {
    \App\Models\Asset::create([
        'asset_no' => 'BBH-LOC-003',
        'asset_name' => 'B. Braun Infusomat Space Volumetric Infusion Pump',
        'status' => 'In Use',
        'location' => null,
        'department' => 'Nursing Service',
        'category_id' => $category->id,
    ]);
}

// 4. Below minimum cost
\App\Models\Setting::set('depreciation_min_cost', '50000');
if (\App\Models\Asset::whereNotNull('cost')->where('cost', '<', 50000)->count() === 0) {
    \App\Models\Asset::create([
        'asset_no' => 'BBH-LOW-004',
        'asset_name' => 'Welch Allyn Digital Diagnostic Otoscope & Ophthalmoscope Set',
        'status' => 'In Use',
        'cost' => 18500.00,
        'location' => 'OPD Consultation Room 1',
        'department' => 'Outpatient Department',
        'category_id' => $category->id,
    ]);
}

$request = \Illuminate\Http\Request::create('/dashboard', 'GET');
$request->setUserResolver(fn () => $admin);
$depreciationService = app(\App\Services\DepreciationService::class);

$controller = new \App\Http\Controllers\DashboardController();
$view = $controller($request, $depreciationService);
$html = $view->render();

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $cssFile = $manifest['resources/css/app.css']['file'] ?? '';
    if ($cssFile) {
        $html = preg_replace('/<link rel="stylesheet" href="[^"]*app-[^"]*\.css"/', '<link rel="stylesheet" href="/build/' . $cssFile . '"', $html);
    }
}

file_put_contents(public_path('dashboard-preview.html'), $html);
echo "Wrote dashboard-preview.html successfully (" . strlen($html) . " bytes)\n";
