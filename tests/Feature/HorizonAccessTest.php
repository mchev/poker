<?php

use Laravel\Horizon\Horizon;

beforeEach(function () {
    config([
        'horizon.allowed_email' => 'martin.chevignard@gmail.com',
        'horizon.allowed_emails' => ['martin.chevignard@gmail.com'],
        'horizon.password' => 'test-horizon-password',
    ]);
});

test('horizon is accessible without credentials in local', function () {
    $this->get('/horizon')
        ->assertOk();
});

test('horizon requires basic auth in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/horizon')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

test('horizon rejects invalid basic auth in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $credentials = base64_encode('martin.chevignard@gmail.com:wrong-password');

    $this->withHeader('Authorization', "Basic {$credentials}")
        ->get('/horizon')
        ->assertUnauthorized();
});

test('horizon accepts valid basic auth in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $credentials = base64_encode('martin.chevignard@gmail.com:test-horizon-password');

    $this->withHeader('Authorization', "Basic {$credentials}")
        ->get('/horizon')
        ->assertOk();
});

test('horizon check accepts configured basic auth credentials', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $request = request()->create('/horizon', 'GET', [], [], [], [
        'PHP_AUTH_USER' => 'martin.chevignard@gmail.com',
        'PHP_AUTH_PW' => 'test-horizon-password',
    ]);

    expect(Horizon::check($request))->toBeTrue();
});
