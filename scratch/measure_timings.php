<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('role', 'admin')->first();
\Illuminate\Support\Facades\Auth::login($user);

$httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$urls = [
    '/dashboard',
    '/assets',
    '/reports',
    '/reports/depreciation',
    '/settings',
];

echo "=== Benchmark Page Timings ===" . PHP_EOL;
foreach ($urls as $url) {
    // Warmup
    $req = \Illuminate\Http\Request::create($url, 'GET');
    $req->setUserResolver(fn() => $user);
    $res = $httpKernel->handle($req);

    // Measured
    $start = microtime(true);
    $req = \Illuminate\Http\Request::create($url, 'GET');
    $req->setUserResolver(fn() => $user);
    $res = $httpKernel->handle($req);
    $duration = round((microtime(true) - $start) * 1000, 2);

    $headerTime = $res->headers->get('X-Response-Time-Ms', 'N/A');
    echo sprintf("%-25s: %8s ms (Kernel total: %s ms)\n", $url, $headerTime, $duration);
}
