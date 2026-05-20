<?php

test('robots are disallowed from crawling the site', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /');
});

test('public pages send noindex robot directives', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
        ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">', false);
});

test('authentication starter routes are disabled', function () {
    $this->get('/login')->assertNotFound();
    $this->get('/dashboard')->assertNotFound();
    $this->get('/settings/profile')->assertNotFound();
});
