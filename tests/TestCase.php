<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'logging.channels.audit.path' => storage_path('logs/audit-testing.log'),
        ]);
    }
}
