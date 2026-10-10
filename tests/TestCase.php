<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if ($this->withoutBootingFramework()) {
            parent::setUp();

            return;
        }

        $this->refreshApplication();

        $database = (string) config('database.connections.mysql.database');
        $cachePath = getenv('APP_CONFIG_CACHE');
        if (! is_string($cachePath) || $cachePath === '') {
            $cachePath = (string) ($_ENV['APP_CONFIG_CACHE'] ?? $_SERVER['APP_CONFIG_CACHE'] ?? '');
        }

        $usingOurCache = $cachePath === '/tmp/nadia-phpunit-config.php';
        $cachedWithoutOurs = $this->app->configurationIsCached() && ! $usingOurCache;

        if ($database !== 'nadia_test' || $cachedWithoutOurs) {
            $this->fail('Refusing to run tests against non-test database');
        }

        parent::setUp();
    }
}
