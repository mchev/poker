<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            str_contains(static::class, '\\Auth\\')
            || str_contains(static::class, '\\Settings\\')
            || str_contains(static::class, '\\DashboardTest')
        ) {
            $this->markTestSkipped('Authentication, dashboard and settings routes are disabled for this poker-only app.');
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
