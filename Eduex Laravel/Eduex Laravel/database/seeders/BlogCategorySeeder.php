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
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Insights, strategies, and resources for modern learning environments.',
                'color' => '#0f9d58',
                'display_order' => 1,
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Everyday living tips that inspire balance, wellness, and productivity.',
                'color' => '#fbbc05',
                'display_order' => 2,
            ],
            [
                'name' => 'Teaching',
                'slug' => 'teaching',
                'description' => 'Practical frameworks and reflections for classroom leaders.',
                'color' => '#4285f4',
                'display_order' => 3,
            ],
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Tools and trends reshaping digital learning and engagement.',
                'color' => '#ab47bc',
                'display_order' => 4,
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
