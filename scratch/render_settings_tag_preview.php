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

$tagAssetService = app(\App\Services\TagAssetService::class);
$backupService = app(\App\Services\BackupService::class);

$sampleAsset = new \App\Models\Asset([
    'asset_no' => (\App\Models\Setting::get('asset_no_prefix', 'BBH') ?: 'BBH') . '-SURG-OR2-2026-00049281',
    'asset_name' => 'Multi-Parameter Vital Signs Monitor with Integrated Capnography & Defibrillator Module',
    'status' => 'In Use',
    'model' => 'Mindray BeneVision N17 Premium Series (High-Acuity ICU / OR)',
    'serial_no' => 'SN-MND-98427189-PH-2026',
    'location' => 'Operating Room Complex Suite 3',
    'department' => 'Department of Surgery & Anesthesia',
    'date_acquired' => now()->subYears(2),
]);

$html = view('settings.index', [
    'hospitalName' => \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
    'hospitalAddress' => \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
    'assetNoPrefix' => \App\Models\Setting::get('asset_no_prefix', 'BBH'),
    'tagWidthCm' => \App\Models\Setting::get('tag_width_cm', '10'),
    'tagHeightCm' => \App\Models\Setting::get('tag_height_cm', '6'),
    'showBarcode' => \App\Models\Setting::get('show_barcode', '0') === '1',
    'backupFolder' => $backupService->getBackupFolder(),
    'logoDataUri' => $tagAssetService->hospitalLogoBase64(),
    'tagAssetService' => $tagAssetService,
    'sampleAsset' => $sampleAsset,
    'backups' => $backupService->listBackups(),
    'backupOverdue' => false,
    'activeDatabasePath' => 'C:\\xampp8.2\\htdocs\\BettleHospital\\database\\database.sqlite',
    'users' => \App\Models\User::orderBy('name')->get(),
    'categories' => \App\Models\Category::withCount('assets')->orderBy('name')->get(),
    'depreciationStartConvention' => 'january_1',
    'depreciationCustomMonth' => 1,
    'depreciationCustomDay' => 1,
    'depreciationDefaultSalvagePercent' => '0',
    'depreciationMinCost' => '0.00',
    'pdfPaperSize' => 'a4',
])->render();

// Switch active tab in static preview to 'tag'
$html = str_replace('id="panel-hospital" role="tabpanel" class="tab-panel space-y-6"', 'id="panel-hospital" role="tabpanel" class="tab-panel hidden space-y-6"', $html);
$html = str_replace('id="panel-tag" role="tabpanel" class="tab-panel hidden space-y-6"', 'id="panel-tag" role="tabpanel" class="tab-panel space-y-6"', $html);
$html = str_replace('id="tab-btn-hospital" role="tab" aria-controls="panel-hospital" aria-selected="true" class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-semibold border-b-2 border-primary text-primary transition-colors flex items-center gap-2"', 'id="tab-btn-hospital" role="tab" aria-controls="panel-hospital" aria-selected="false" class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 transition-colors flex items-center gap-2"', $html);
$html = str_replace('id="tab-btn-tag" role="tab" aria-controls="panel-tag" aria-selected="false" class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 transition-colors flex items-center gap-2"', 'id="tab-btn-tag" role="tab" aria-controls="panel-tag" aria-selected="true" class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-semibold border-b-2 border-primary text-primary transition-colors flex items-center gap-2"', $html);

file_put_contents(__DIR__ . '/../public/tag_preview.html', $html);
echo "tag_preview.html generated successfully (" . strlen($html) . " bytes)\n";
