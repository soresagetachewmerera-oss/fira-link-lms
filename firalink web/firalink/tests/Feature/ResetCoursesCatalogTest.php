<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResetCoursesCatalogTest extends TestCase
{
    // Not RefreshDatabase: the suite shares one migrated, seeded in-memory database (tests/TestCase.php).
    // Roles, course categories and languages come from TestingSeeder; the transaction rolls back.
    use DatabaseTransactions;

    public function test_reset_command_seeds_eleven_official_free_courses(): void
    {
        $category = \App\Models\CourseCategory::firstOrFail();
        $language = \App\Models\CourseLanguage::firstOrFail();
        $dummyInstructor = \App\Models\User::factory()->create(['role' => 'instructor']);

        $before = Course::count();
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

        $this->assertEquals($before + 1, Course::count());

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
