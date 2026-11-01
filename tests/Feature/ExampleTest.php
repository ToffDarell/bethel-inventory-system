<?php

test('the root url redirects guests to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
