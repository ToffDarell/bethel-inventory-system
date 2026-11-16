<?php

$cookieFile = sys_get_temp_dir() . '/bethel_perf_cookies_' . time() . '.txt';

function makeRequest(string $url, ?string $postData = null) {
    global $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }

    $start = microtime(true);
    $response = curl_exec($ch);
    $totalDuration = (microtime(true) - $start) * 1000;
    
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $info['http_code'],
        'namelookup_ms' => round($info['namelookup_time'] * 1000, 2),
        'connect_ms' => round($info['connect_time'] * 1000, 2),
        'starttransfer_ttfb_ms' => round($info['starttransfer_time'] * 1000, 2),
        'total_ms' => round($info['total_time'] * 1000, 2),
        'size_bytes' => $info['size_download'],
        'effective_url' => $info['url'],
        'error' => $error,
        'response_preview' => substr((string)$response, 0, 100),
    ];
}

echo "=== REAL HTTP SERVER BENCHMARK (http://127.0.0.1:8000) ===\n\n";

// 1. Fetch login page to get CSRF token
$loginPage = makeRequest('http://127.0.0.1:8000/login');
preg_match('/name="_token" value="([^"]+)"/', $loginPage['response_preview'] . (string)file_get_contents('http://127.0.0.1:8000/login'), $matches);
$csrfToken = $matches[1] ?? '';

// 2. Perform Login as admin
$loginRes = makeRequest('http://127.0.0.1:8000/login', http_build_query([
    '_token' => $csrfToken,
    'username' => 'admin',
    'password' => 'password123',
]));
echo "Login status: HTTP " . $loginRes['http_code'] . " in " . $loginRes['total_ms'] . " ms\n\n";

$pages = [
    'Dashboard' => 'http://127.0.0.1:8000/dashboard',
    'Assets' => 'http://127.0.0.1:8000/assets',
    'Reports' => 'http://127.0.0.1:8000/reports',
    'Depreciation' => 'http://127.0.0.1:8000/reports/depreciation',
    'Settings' => 'http://127.0.0.1:8000/settings',
];

echo str_repeat('-', 90) . "\n";
printf("%-20s | %-12s | %-12s | %-12s | %-12s | %-8s\n", "Page", "Cold TTFB", "Cold Total", "Warm TTFB", "Warm Total", "Status");
echo str_repeat('-', 90) . "\n";

foreach ($pages as $name => $url) {
    // Cold request
    $cold = makeRequest($url);
    
    // Warm request 1
    $warm1 = makeRequest($url);
    // Warm request 2
    $warm2 = makeRequest($url);
    
    $warmAvgTotal = round(($warm1['total_ms'] + $warm2['total_ms']) / 2, 1);
    $warmAvgTtfb = round(($warm1['starttransfer_ttfb_ms'] + $warm2['starttransfer_ttfb_ms']) / 2, 1);

    printf("%-20s | %10.1fms | %10.1fms | %10.1fms | %10.1fms | %-8d\n",
        $name,
        $cold['starttransfer_ttfb_ms'],
        $cold['total_ms'],
        $warmAvgTtfb,
        $warmAvgTotal,
        $cold['http_code']
    );
}

echo str_repeat('-', 90) . "\n";

@unlink($cookieFile);
