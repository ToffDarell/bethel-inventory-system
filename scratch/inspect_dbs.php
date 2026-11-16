<?php

$dbs = [
    'Downloads' => __DIR__ . '/../Downloads/pre-migration-2026-11-05_193355.sqlite',
    'Desktop' => 'C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite'
];

foreach ($dbs as $name => $path) {
    echo "=== DB: $name ($path) ===" . PHP_EOL;
    if (!file_exists($path)) {
        echo "File does not exist: $path" . PHP_EOL;
        continue;
    }
    $pdo = new PDO('sqlite:' . $path);
    echo "--- CATEGORIES ---" . PHP_EOL;
    $cats = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cats as $c) {
        echo json_encode($c) . PHP_EOL;
    }
    echo "--- ASSETS (with cost) ---" . PHP_EOL;
    $assets = $pdo->query('SELECT id, asset_no, asset_name, category_id, cost, salvage_value, useful_life_years, date_acquired FROM assets WHERE cost IS NOT NULL AND cost > 0')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($assets as $a) {
        echo json_encode($a) . PHP_EOL;
    }
    echo "--- SETTINGS ---" . PHP_EOL;
    $settings = $pdo->query('SELECT * FROM settings')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settings as $s) {
        echo json_encode($s) . PHP_EOL;
    }
}
