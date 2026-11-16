<?php

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\Department;
use App\Models\Setting;
use App\Services\AssetNumberService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

test('allocates sequential asset numbers and advances next_number', function () {
    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('asset_number_digits', '4');
    Setting::set('asset_number_scope', 'department');

    $dept = Department::create([
        'name' => 'Emergency Room ' . uniqid(),
        'code' => 'ER',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $service = new AssetNumberService();
    $num1 = $service->generateNextNumber($dept);
    expect($num1)->toBe('BBHER-0001');

    $dept->refresh();
    expect($dept->next_number)->toBe(2);

    $num2 = $service->generateNextNumber($dept);
    expect($num2)->toBe('BBHER-0002');
});

test('two departments get independent counters', function () {
    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('asset_number_digits', '4');
    Setting::set('asset_number_scope', 'department');

    $deptER = Department::create([
        'name' => 'Emergency Room ' . uniqid(),
        'code' => 'ER',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $deptSurg = Department::create([
        'name' => 'Surgery Department ' . uniqid(),
        'code' => 'SURG',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $service = new AssetNumberService();

    // Allocate for ER
    $er1 = $service->generateNextNumber($deptER);
    expect($er1)->toBe('BBHER-0001');
    expect($deptER->fresh()->next_number)->toBe(2);

    // Allocate for SURG: independent counter starting at 1
    $surg1 = $service->generateNextNumber($deptSurg);
    expect($surg1)->toBe('BBHSURG-0001');
    expect($deptSurg->fresh()->next_number)->toBe(2);

    // Allocate for ER again: advances to 2 while SURG remains at 2
    $er2 = $service->generateNextNumber($deptER);
    expect($er2)->toBe('BBHER-0002');
    expect($deptER->fresh()->next_number)->toBe(3);
    expect($deptSurg->fresh()->next_number)->toBe(2);
});

test('never reuses a number from active, archived assets, or history', function () {
    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('asset_number_digits', '4');
    Setting::set('asset_number_scope', 'department');

    $dept = Department::create([
        'name' => 'Biomedical ' . uniqid(),
        'code' => 'BIOMED',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    // Pre-create an archived asset with BIOMED-0001
    Asset::create([
        'asset_no' => 'BBHBIOMED-0001',
        'asset_name' => 'Old Sensor',
        'department_id' => $dept->id,
        'status' => 'In Use',
    ])->delete(); // Soft-deleted

    // Pre-record BBHBIOMED-0002 in number history
    $activeAsset = Asset::create([
        'asset_no' => 'BBHBIOMED-0099',
        'asset_name' => 'Monitor',
        'department_id' => $dept->id,
        'status' => 'In Use',
    ]);
    AssetNumberHistory::create([
        'asset_id' => $activeAsset->id,
        'old_no' => 'BBHBIOMED-0002',
        'new_no' => 'BBHBIOMED-0099',
        'reason' => 'Reassigned',
    ]);

    $service = new AssetNumberService();
    // Since 0001 is archived and 0002 is in history, next allocated must be 0003!
    $num = $service->generateNextNumber($dept);
    expect($num)->toBe('BBHBIOMED-0003');
});

test('refuses to generate auto number if department code is not confirmed', function () {
    $dept = Department::create([
        'name' => 'Unconfirmed Dept ' . uniqid(),
        'code' => 'UNCONF',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => false,
    ]);

    $service = new AssetNumberService();
    expect(fn () => $service->generateNextNumber($dept))->toThrow(RuntimeException::class);
});

test('concurrency: 50 parallel PHP CLI processes generate 50 unique non-duplicate numbers on SQLite', function () {
    // Set up a real file-based SQLite database with WAL mode for parallel processes
    $tempDbPath = storage_path('framework/testing_concurrency.sqlite');
    if (File::exists($tempDbPath)) {
        File::delete($tempDbPath);
    }
    touch($tempDbPath);

    $workerScript = storage_path('framework/concurrency_worker.php');
    $phpBinary = PHP_BINARY;

    try {
        // Initialize the temp DB in an isolated child CLI process so parent test process state is completely untouched
        $initScript = storage_path('framework/concurrency_init.php');
        $initCode = <<<'PHP'
<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tempDb = $argv[1];
config(['database.connections.sqlite.database' => $tempDb]);
\Illuminate\Support\Facades\DB::purge('sqlite');
\Illuminate\Support\Facades\DB::reconnect('sqlite');
\Illuminate\Support\Facades\DB::statement('PRAGMA journal_mode = WAL;');
\Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 15000;');
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

\App\Models\Setting::set('asset_no_prefix', 'BBH');
\App\Models\Setting::set('asset_number_digits', '4');
\App\Models\Setting::set('asset_number_scope', 'department');

$dept = \App\Models\Department::create([
    'name' => 'Concurrent Surgery',
    'code' => 'SURG',
    'next_number' => 1,
    'active' => true,
    'code_confirmed' => true,
]);

echo $dept->id;
PHP;
        File::put($initScript, $initCode);
        $initOutput = shell_exec("\"{$phpBinary}\" \"{$initScript}\" \"{$tempDbPath}\"");
        $deptId = (int) trim($initOutput);
        File::delete($initScript);

        expect($deptId)->toBeGreaterThan(0);

        // Create a standalone worker script to be executed by parallel processes
        $workerCode = <<<'PHP'
<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tempDb = $argv[1];
config(['database.connections.sqlite.database' => $tempDb]);
\Illuminate\Support\Facades\DB::purge('sqlite');
\Illuminate\Support\Facades\DB::reconnect('sqlite');
\Illuminate\Support\Facades\DB::statement('PRAGMA journal_mode = WAL;');
\Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 15000;');

$deptId = (int) $argv[2];
$dept = \App\Models\Department::find($deptId);

$service = new \App\Services\AssetNumberService();
try {
    $num = $service->generateNextNumber($dept);
    \App\Models\Asset::create([
        'asset_no' => $num,
        'asset_name' => 'Concurrent Item ' . uniqid(),
        'department_id' => $dept->id,
        'status' => 'In Use',
    ]);
    echo "SUCCESS:" . $num . "\n";
} catch (\Throwable $e) {
    echo "ERROR:" . $e->getMessage() . "\n";
}
PHP;
        File::put($workerScript, $workerCode);

        $processCount = 50;
        $processes = [];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Spawn 50 real parallel PHP processes
        for ($i = 0; $i < $processCount; $i++) {
            $cmd = "\"{$phpBinary}\" \"{$workerScript}\" \"{$tempDbPath}\" {$deptId}";
            $proc = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($proc)) {
                fclose($pipes[0]);
                $processes[] = [
                    'resource' => $proc,
                    'stdout' => $pipes[1],
                    'stderr' => $pipes[2],
                ];
            }
        }

        $outputs = [];
        foreach ($processes as $p) {
            $out = stream_get_contents($p['stdout']);
            $err = stream_get_contents($p['stderr']);
            fclose($p['stdout']);
            fclose($p['stderr']);
            proc_close($p['resource']);
            $outputs[] = trim($out);
        }

        $successfulNumbers = [];
        foreach ($outputs as $out) {
            if (str_starts_with($out, 'SUCCESS:')) {
                $successfulNumbers[] = substr($out, 8);
            }
        }

        expect(count($successfulNumbers))->toBe(50);
        // Assert 50 unique non-duplicate numbers!
        $uniqueNumbers = array_unique($successfulNumbers);
        expect(count($uniqueNumbers))->toBe(50);
    } finally {
        if (File::exists($workerScript)) {
            File::delete($workerScript);
        }
        if (File::exists($tempDbPath)) {
            File::delete($tempDbPath);
        }
    }
});
