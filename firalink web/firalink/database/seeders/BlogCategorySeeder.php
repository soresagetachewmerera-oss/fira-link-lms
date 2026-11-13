<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fira Link Platform',
                'slug' => 'fira-link-platform',
                'description' => 'Digital supply chain traceability, direct trade auctions, and B2B coffee matchmaking.',
                'color' => '#1b703c',
                'display_order' => 1,
            ],
            [
                'name' => 'Coffee Heritage',
                'slug' => 'coffee-heritage',
                'description' => 'Ancient Ethiopian coffee rituals, traditional Buna ceremonies, and indigenous forest heritage.',
                'color' => '#b85d19',
                'display_order' => 2,
            ],
            [
                'name' => 'Grading & Cupping',
                'slug' => 'grading-cupping',
                'description' => 'SCA sensory evaluation, Q-Grader scoring protocols, and green bean defect classification.',
                'color' => '#6d3b14',
                'display_order' => 3,
            ],
            [
                'name' => 'Processing & Roasting',
                'slug' => 'processing-roasting',
                'description' => 'Natural sun-drying, raised African beds, wet-milling, and advanced anaerobic fermentation.',
                'color' => '#8f2d56',
                'display_order' => 4,
            ],
            [
                'name' => 'Export & Trade',
                'slug' => 'export-trade',
                'description' => 'ECTA compliance, direct export licensing, ECX market pricing, and international logistics.',
                'color' => '#1c528a',
                'display_order' => 5,
            ],
            [
                'name' => 'Origin & Terroir',
                'slug' => 'origin-terroir',
                'description' => 'Deep exploration of Yirgacheffe, Sidama, Guji, Harar, and Kaffa highland growing regions.',
                'color' => '#2e7d32',
                'display_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug(Arr::get($category, 'slug', $category['name']));

            BlogCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $category['name'],
                    'description' => Arr::get($category, 'description'),
                    'color' => Arr::get($category, 'color'),
                    'is_active' => Arr::get($category, 'is_active', true),
                    'display_order' => Arr::get($category, 'display_order', 0),
                ]
            );
        }
    }
}
