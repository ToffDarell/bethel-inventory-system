<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$admin = \App\Models\User::where('role', 'admin')->first();
if (! $admin) {
    $admin = \App\Models\User::first();
}

$routes = [
    'GET /login' => ['uri' => '/login', 'auth' => false],
    'GET /dashboard' => ['uri' => '/dashboard', 'auth' => true],
    'GET /assets' => ['uri' => '/assets', 'auth' => true],
    'GET /reports' => ['uri' => '/reports', 'auth' => true],
    'GET /reports/depreciation' => ['uri' => '/reports/depreciation', 'auth' => true],
    'GET /settings' => ['uri' => '/settings', 'auth' => true],
];

echo "BENCHMARK RESULTS (Packaged / Local Application Engine):\n";
echo str_repeat('-', 70) . "\n";
printf("%-28s | %-12s | %-10s | %-10s\n", "Route", "Latency (ms)", "Status", "Queries");
echo str_repeat('-', 70) . "\n";

foreach ($routes as $label => $info) {
    if ($info['auth'] && $admin) {
        \Illuminate\Support\Facades\Auth::login($admin);
    } else {
        \Illuminate\Support\Facades\Auth::logout();
    }

    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $request = \Illuminate\Http\Request::create($info['uri'], 'GET');
    $start = microtime(true);
    $response = $kernel->handle($request);
    $durationMs = round((microtime(true) - $start) * 1000, 1);
    $queries = count(\Illuminate\Support\Facades\DB::getQueryLog());

    printf("%-28s | %10.1fms | %-10d | %-10d\n", $label, $durationMs, $response->getStatusCode(), $queries);

    $kernel->terminate($request, $response);
}
echo str_repeat('-', 70) . "\n";
