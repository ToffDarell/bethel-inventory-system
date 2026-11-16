<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$candidates = [
    'Downloads/pre-migration-2026-11-05_193355.sqlite',
    'database/database.sqlite',
];

foreach ($candidates as $cand) {
    if (file_exists($cand)) {
        echo "Candidate: $cand (" . filesize($cand) . " bytes)\n";
        $pdo = new PDO("sqlite:" . realpath($cand));
        $count = $pdo->query("SELECT count(*) FROM assets WHERE deleted_at IS NULL")->fetchColumn();
        echo "  Active assets count: $count\n";
    }
}
