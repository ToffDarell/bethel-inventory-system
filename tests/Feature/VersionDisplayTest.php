<?php

use App\Models\User;

test('application version is configured as 1.3.6 in nativephp configuration', function () {
    expect(config('nativephp.version'))->toBe('1.3.6');
});

test('settings diagnostics displays version 1.3.6', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $response = $this->actingAs($admin)->get('/settings?tab=backup');

    $response->assertOk();
    $response->assertSee('v1.3.6');
});
