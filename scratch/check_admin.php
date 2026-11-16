<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\User::all() as $u) {
    echo $u->id . ': ' . $u->username . ' | role=' . $u->role . ' | must_change=' . ($u->must_change_password ? '1' : '0') . "\n";
}
