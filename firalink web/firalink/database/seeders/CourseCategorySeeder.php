<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CourseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Development',
                'slug' => 'development',
                'description' => 'Coding, web, and software engineering programs for all skill levels.',
                'icon' => 'fas fa-code',
                'display_order' => 1,
            ],
            [
                'name' => 'Design',
                'slug' => 'design',
                'description' => 'UI/UX, graphic design, and creative direction courses.',
                'icon' => 'fas fa-pencil-ruler',
                'display_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Entrepreneurship, marketing, and leadership training.',
                'icon' => 'fas fa-briefcase',
                'display_order' => 3,
            ],
            [
                'name' => 'Education & Teaching',
                'slug' => 'education-teaching',
                'description' => 'Instructional design, pedagogy, and classroom strategies.',
                'icon' => 'fas fa-chalkboard-teacher',
                'display_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug(Arr::get($category, 'slug', $category['name']));

            CourseCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $category['name'],
                    'description' => Arr::get($category, 'description'),
                    'icon' => Arr::get($category, 'icon'),
                    'is_active' => Arr::get($category, 'is_active', true),
                    'display_order' => Arr::get($category, 'display_order', 0),
                ]
            );
        }
    }
}
