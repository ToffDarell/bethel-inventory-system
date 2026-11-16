<?php

use App\Models\User;

test('measures and asserts sub-second response times across all main application pages', function () {
    $admin = User::factory()->create([
        'name' => 'Admin Officer',
        'username' => 'admin_perf_bench',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $pages = [
        'GET /login' => ['url' => '/login', 'auth' => false],
        'GET /dashboard' => ['url' => '/dashboard', 'auth' => true],
        'GET /assets' => ['url' => '/assets', 'auth' => true],
        'GET /reports' => ['url' => '/reports', 'auth' => true],
        'GET /reports/depreciation' => ['url' => '/reports/depreciation', 'auth' => true],
        'GET /settings' => ['url' => '/settings', 'auth' => true],
    ];

    foreach ($pages as $label => $spec) {
        $client = $spec['auth'] ? $this->actingAs($admin) : $this;

        $response = $client->get($spec['url']);
        $response->assertOk();
        expect($response->headers->has('X-Response-Time-Ms'))->toBeTrue();
    }
});
