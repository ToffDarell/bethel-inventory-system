<?php
$pdo = new PDO('sqlite:Downloads/pre-migration-2026-11-05_193355.sqlite');
$cols = $pdo->query("PRAGMA table_info(assets)")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns in assets in Downloads:\n";
foreach ($cols as $c) {
    echo " - " . $c['name'] . "\n";
}
echo "Migrations in Downloads:\n";
$m = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
foreach ($m as $mig) {
    echo " - " . $mig . "\n";
}
