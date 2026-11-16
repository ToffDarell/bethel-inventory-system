<?php

test('the root url redirects guests to login when an administrator exists', function () {
    \App\Models\User::factory()->create(['role' => 'admin']);

    $response = $this->get('/');

    $response->assertRedirect('/login');
});
