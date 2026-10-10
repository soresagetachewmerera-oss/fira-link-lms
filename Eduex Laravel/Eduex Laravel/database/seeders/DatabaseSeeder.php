<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            PaymentGatewaySettingSeeder::class,
            PlatformSettingSeeder::class,
            CourseCategorySeeder::class,
            CourseLanguageSeeder::class,
            BlogCategorySeeder::class,
            BlogPostSeeder::class,
            MenuSeeder::class,
            FrontendSettingSeeder::class,
            CustomPageSeeder::class,
        ]);
    }
}
