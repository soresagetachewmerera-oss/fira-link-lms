<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\CourseLanguageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetCoursesCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_command_seeds_eleven_official_free_courses(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(CourseCategorySeeder::class);
        $this->seed(CourseLanguageSeeder::class);

        // Create a dummy legacy course
        $category = \App\Models\CourseCategory::first();
        $language = \App\Models\CourseLanguage::first();
        $dummyInstructor = \App\Models\User::factory()->create(['role' => 'instructor']);

        Course::create([
            'instructor_id' => $dummyInstructor->id,
            'title' => 'Legacy Paid Test Course That Should Be Removed',
            'description' => 'Test',
            'course_category_id' => $category->id,
            'course_language_id' => $language->id,
            'pricing_model' => 'paid',
            'regular_price' => 500,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $this->assertEquals(1, Course::count());

        $this->artisan('courses:reset-to-free-catalog', ['--force' => true])
            ->assertSuccessful();

        $this->assertEquals(11, Course::count());
        $this->assertDatabaseMissing('courses', [
            'title' => 'Legacy Paid Test Course That Should Be Removed',
        ]);

        $courses = Course::all();
        foreach ($courses as $course) {
            $this->assertEquals('free', $course->pricing_model);
            $this->assertEquals(0, $course->regular_price);
            $this->assertEquals('published', $course->status);
        }
    }
}
