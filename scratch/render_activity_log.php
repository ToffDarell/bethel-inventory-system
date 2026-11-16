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

// Ensure we have sample activities with readable changes if needed
$sampleAsset = \App\Models\Asset::first();
if ($sampleAsset) {
    activity()
        ->performedOn($sampleAsset)
        ->causedBy($admin)
        ->withProperties([
            'old' => [
                'location' => 'Emergency Room',
                'asset_condition' => 'In Good Condition',
                'department' => 'Emergency',
            ],
            'attributes' => [
                'location' => 'Intensive Care Unit (ICU)',
                'asset_condition' => 'Damage',
                'department' => 'Critical Care',
            ],
        ])
        ->log("Updated asset {$sampleAsset->asset_no}");

    activity()
        ->causedBy($admin)
        ->log("Depreciation start convention changed from 'january_1' to 'july_1' by {$admin->name}");

    activity()
        ->causedBy($admin)
        ->withProperties(['backup_file' => 'bethel-backup-manual-2026-11-07.sqlite'])
        ->log("Created manual database backup: bethel-backup-manual-2026-11-07.sqlite");
}

$activities = \Spatie\Activitylog\Models\Activity::with(['causer', 'subject'])
    ->latest('id')
    ->paginate(15);

$html = view('settings.activity-log', [
    'activities' => $activities,
    'currentFilter' => 'all',
    'search' => '',
])->render();

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $cssFile = $manifest['resources/css/app.css']['file'] ?? '';
    if ($cssFile) {
        $html = preg_replace('/<link rel="stylesheet" href="[^"]*app-[^"]*\.css"/', '<link rel="stylesheet" href="/build/' . $cssFile . '"', $html);
    }
}

file_put_contents(public_path('activity-log-preview.html'), $html);
echo "Wrote activity-log-preview.html successfully (" . strlen($html) . " bytes)\n";
