<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Reference data the test suite expects (roles, settings, plans, export templates), for the
 * in-memory test database (DB_DATABASE=:memory:). No demo prices or demo market benchmarks.
 */
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
            SaasPlanSeeder::class,
            ExportDocumentTemplateSeeder::class,
        ]);
    }
}
