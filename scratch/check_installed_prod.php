<?php
$pdo = new PDO("sqlite:C:/xampp8.2/htdocs/BettleHospital/scratch/temp_installed_prod.sqlite");
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

echo "Installed Prod Tables: " . implode(', ', $tables) . "\n";

if (in_array('activity_log', $tables)) {
    $rows = $pdo->query("SELECT id, description, properties, created_at FROM activity_log ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "#" . $r['id'] . " | " . $r['description'] . " | " . $r['created_at'] . " | " . substr($r['properties'], 0, 100) . "\n";
    }
}

if (in_array('assets', $tables)) {
    $assets = $pdo->query("SELECT * FROM assets")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nAll Assets in installed prod:\n";
    foreach ($assets as $a) {
        echo "ID: " . $a['id'] . " | No: " . $a['asset_no'] . " | Name: " . ($a['asset_name'] ?? 'N/A') . " | Dept: " . ($a['department'] ?? 'null') . " | Loc: " . ($a['location'] ?? 'null') . "\n";
    }
}
