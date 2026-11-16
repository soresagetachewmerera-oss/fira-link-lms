<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * With DB_DATABASE=:memory: every test gets a new app and so a new, empty database.
     * Migrate and seed reference data (TestingSeeder) once per run, then hand the same PDO to each
     * new app before its providers boot (providers read settings tables at boot) and before traits
     * such as DatabaseTransactions open their transaction, so tests still roll back.
     * The file database (database/database.sqlite) is never touched in this mode.
     */
    protected static ?\PDO $memoryPdo = null;

    public function createApplication()
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        $app->beforeBootstrapping(BootProviders::class, function ($app) {
            if (static::$memoryPdo && self::usesMemoryDatabase($app)) {
                $app['db']->connection()->setPdo(static::$memoryPdo)->setReadPdo(static::$memoryPdo);
            }
        });

        $app->make(Kernel::class)->bootstrap();

        if (!static::$memoryPdo && self::usesMemoryDatabase($app)) {
            $app[Kernel::class]->call('migrate', ['--force' => true]);
            $app[Kernel::class]->call('db:seed', ['--class' => \Database\Seeders\TestingSeeder::class, '--force' => true]);
            static::$memoryPdo = $app['db']->connection()->getPdo();
            $app->flush();

            // Build the app again so its providers boot against the migrated, seeded database.
            return $this->createApplication();
        }

        return $app;
    }

    private static function usesMemoryDatabase($app): bool
    {
        return $app['config']->get('database.default') === 'sqlite'
            && $app['config']->get('database.connections.sqlite.database') === ':memory:';
    }
}
