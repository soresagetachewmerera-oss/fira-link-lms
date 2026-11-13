<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Habit;
use Tests\TestCase;

class StudyFocusHabitTest extends TestCase
{
    public function test_authenticated_student_can_store_and_retrieve_focus_sessions(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'focus-student-' . uniqid() . '@example.com',
            'phone' => '+251911' . rand(100000, 999999),
            'phone_verified_at' => now(),
        ]);

        $storeResponse = $this->actingAs($student, 'sanctum')->postJson('/api/focus/session', [
            'duration' => 25,
            'type' => 'pomodoro',
            'status' => 'completed',
            'start_time' => now()->subMinutes(25)->toIso8601String(),
            'end_time' => now()->toIso8601String(),
        ]);

        $storeResponse->assertStatus(200);
        $storeResponse->assertJson(['success' => true]);

        $statsResponse = $this->actingAs($student, 'sanctum')->getJson('/api/focus/stats');
        $statsResponse->assertStatus(200);
        $statsResponse->assertJson(['success' => true]);
    }

    public function test_authenticated_student_can_manage_habits_and_log(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'habit-student-' . uniqid() . '@example.com',
            'phone' => '+251911' . rand(100000, 999999),
            'phone_verified_at' => now(),
        ]);

        $createResponse = $this->actingAs($student, 'sanctum')->postJson('/api/habits', [
            'name' => 'Review Weekly ECX Floor Prices',
            'target_days_per_week' => 5,
        ]);

        $createResponse->assertStatus(200);
        $habitId = $createResponse->json('data.id');

        $logResponse = $this->actingAs($student, 'sanctum')->postJson("/api/habits/{$habitId}/log", [
            'date' => now()->toDateString(),
        ]);
        $logResponse->assertStatus(200);

        $weeklyReviewResponse = $this->actingAs($student, 'sanctum')->getJson('/api/habits/weekly-review');
        $weeklyReviewResponse->assertStatus(200);
        $weeklyReviewResponse->assertJson(['success' => true]);
    }
}
