<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
foreach ($pdo->query('PRAGMA table_info(categories)')->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['name'] . ' | ' . $c['type'] . ' | notnull=' . $c['notnull'] . ' | dflt=' . $c['dflt_value'] . PHP_EOL;
}
