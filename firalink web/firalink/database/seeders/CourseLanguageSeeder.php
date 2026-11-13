<?php

namespace Database\Seeders;

use App\Models\CourseLanguage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CourseLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'English',
                'code' => 'en',
                'native_name' => 'English',
                'display_order' => 1,
            ],
            [
                'name' => 'Spanish',
                'code' => 'es',
                'native_name' => 'Español',
                'display_order' => 2,
            ],
            [
                'name' => 'French',
                'code' => 'fr',
                'native_name' => 'Français',
                'display_order' => 3,
            ],
            [
                'name' => 'German',
                'code' => 'de',
                'native_name' => 'Deutsch',
                'display_order' => 4,
            ],
        ];

        foreach ($languages as $language) {
            CourseLanguage::updateOrCreate(
                ['code' => $language['code']],
                [
                    'name' => $language['name'],
                    'native_name' => Arr::get($language, 'native_name'),
                    'is_active' => Arr::get($language, 'is_active', true),
                    'display_order' => Arr::get($language, 'display_order', 0),
                ]
            );
        }
    }
}
