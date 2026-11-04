<?php

test('registration screen redirects to login in internal erp', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('login'));
});
