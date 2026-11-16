<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dbPath = 'Downloads/pre-migration-2026-11-05_193355.sqlite';
$pdo = new PDO("sqlite:" . realpath($dbPath));

echo "=== Categories in backup ===" . PHP_EOL;
$cats = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
print_r($cats);

echo "=== Assets in backup ===" . PHP_EOL;
$assets = $pdo->query("SELECT id, asset_no, asset_name, category_id, cost, salvage_value, useful_life_years, date_acquired, acquired_year_only FROM assets WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
print_r($assets);

echo "=== Assets in active database ===" . PHP_EOL;
$pdoActive = new PDO("sqlite:" . realpath('database/database.sqlite'));
$assetsActive = $pdoActive->query("SELECT id, asset_no, asset_name, category_id, cost, salvage_value, useful_life_years, date_acquired, acquired_year_only FROM assets WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
print_r($assetsActive);
