<?php

$dbs = [
    'Downloads' => __DIR__ . '/../Downloads/pre-migration-2026-11-05_193355.sqlite',
    'Desktop' => 'C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite'
];

foreach ($dbs as $name => $path) {
    echo "=== DB: $name ($path) ===" . PHP_EOL;
    $pdo = new PDO('sqlite:' . $path);
    $assets = $pdo->query('SELECT id, asset_no, asset_name, category_id, cost, salvage_value, useful_life_years, date_acquired FROM assets WHERE deleted_at IS NULL')->fetchAll(PDO::FETCH_ASSOC);
    echo "Total active assets: " . count($assets) . PHP_EOL;
    foreach ($assets as $a) {
        echo json_encode($a) . PHP_EOL;
    }
}
