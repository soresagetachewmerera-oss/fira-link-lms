<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\User;
use App\Notifications\LiveClassNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

/**
 * LiveClassService
 *
 * Handles all business logic for scheduling, managing, and notifying about live classes.
 *
 * @package App\Services
 */
class LiveClassService
{
    public function __construct(
        protected SettingsRepository $settings,
        protected FileUploadService $fileUploadService,
    ) {}

    /**
     * Schedule a new live class.
     */
    public function schedule(array $data, User $instructor): LiveClass
    {
        $liveClass = LiveClass::create([
            'course_id'        => $data['course_id'],
            'lesson_id'        => $data['lesson_id'] ?? null,
            'instructor_id'    => $instructor->id,
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'join_url'         => $data['join_url'],
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'status'           => LiveClass::STATUS_SCHEDULED,
        ]);

        // If linked to a lesson, mark lesson as live
        if (!empty($data['lesson_id'])) {
            Lesson::where('id', $data['lesson_id'])->update([
                'is_live'       => true,
                'live_class_id' => $liveClass->id,
            ]);
        }

        // Notify enrolled students
        $this->notifyEnrolledStudents($liveClass, 'scheduled');

        return $liveClass;
    }

    /**
     * Update an existing live class.
     */
    public function update(LiveClass $liveClass, array $data): LiveClass
    {
        $liveClass->update([
            'course_id'        => $data['course_id'] ?? $liveClass->course_id,
            'lesson_id'        => $data['lesson_id'] ?? $liveClass->lesson_id,
            'title'            => $data['title'] ?? $liveClass->title,
            'description'      => $data['description'] ?? $liveClass->description,
            'join_url'         => $data['join_url'] ?? $liveClass->join_url,
            'scheduled_at'     => $data['scheduled_at'] ?? $liveClass->scheduled_at,
            'duration_minutes' => $data['duration_minutes'] ?? $liveClass->duration_minutes,
        ]);

        return $liveClass->fresh();
    }

    /**
     * Mark session as live and notify enrolled students.
     */
    public function startSession(LiveClass $liveClass): void
    {
        $liveClass->update(['status' => LiveClass::STATUS_LIVE]);
        $this->notifyEnrolledStudents($liveClass, 'starting_now');
    }

    /**
     * End session, optionally attach a recording.
     */
    public function endSession(
        LiveClass $liveClass,
        ?string $recordingUrl = null,
        ?UploadedFile $recordingFile = null
    ): void {
        $recordingPath = null;

        if ($recordingFile) {
            $recordingPath = $this->fileUploadService->uploadLessonVideo($recordingFile);
        }

        $liveClass->update([
            'status'               => LiveClass::STATUS_ENDED,
            'recording_url'        => $recordingUrl,
            'recording_video_path' => $recordingPath,
        ]);

        // If linked to a lesson, update lesson to carry the recording
        if ($liveClass->lesson_id) {
            $lesson = Lesson::find($liveClass->lesson_id);
            if ($lesson) {
                if ($recordingPath) {
                    $lesson->update([
                        'video_type' => 'upload',
                        'video_path' => $recordingPath,
                        'video_url'  => null,
                    ]);
                } elseif ($recordingUrl) {
                    $lesson->update([
                        'video_type' => 'url',
                        'video_url'  => $recordingUrl,
                        'video_path' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Cancel a scheduled live class.
     */
    public function cancel(LiveClass $liveClass): void
    {
        $liveClass->update(['status' => LiveClass::STATUS_CANCELLED]);
        $this->notifyEnrolledStudents($liveClass, 'cancelled');
    }

    /**
     * Send reminder notifications to enrolled students (called by scheduler).
     */
    public function sendReminder(LiveClass $liveClass): void
    {
        $this->notifyEnrolledStudents($liveClass, 'reminder');
    }

    /**
     * Find all live classes that need reminders sent right now.
     */
    public function getDueForReminder(): \Illuminate\Database\Eloquent\Collection
    {
        $minutesBefore = (int) ($this->settings->get('live_class_reminder_minutes', 30));

        $windowStart = now()->addMinutes($minutesBefore - 2);
        $windowEnd   = now()->addMinutes($minutesBefore + 2);

        return LiveClass::where('status', LiveClass::STATUS_SCHEDULED)
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereDoesntHave('remindersAlreadySent') // uses meta check below
            ->with(['course', 'instructor'])
            ->get();
    }

    /**
     * Notify all enrolled (approved/completed) students of a course.
     */
    public function notifyEnrolledStudents(LiveClass $liveClass, string $notificationType): void
    {
        $liveClass->loadMissing(['course', 'instructor']);

        $students = User::whereHas('enrollments', function ($q) use ($liveClass) {
            $q->where('course_id', $liveClass->course_id)
              ->whereIn('status', ['approved', 'completed']);
        })->get();

        foreach ($students as $student) {
            try {
                $student->notify(new LiveClassNotification($liveClass, $notificationType));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send Live Class notification to student ID {$student->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get upcoming live classes for a student (enrolled courses only).
     */
    public function getUpcomingForStudent(User $student): ?\App\Models\LiveClass
    {
        $enrolledCourseIds = Enrollment::where('user_id', $student->id)
            ->whereIn('status', ['approved', 'completed'])
            ->pluck('course_id');

        return LiveClass::whereIn('course_id', $enrolledCourseIds)
            ->where(function ($q) {
                $q->where('status', LiveClass::STATUS_SCHEDULED)
                  ->orWhere('status', LiveClass::STATUS_LIVE);
            })
            ->where('scheduled_at', '>=', now()->subHours(2)) // still show if started up to 2h ago
            ->orderBy('scheduled_at')
            ->with('course')
            ->first();
    }

    /**
     * Get all upcoming live classes for a student.
     */
    public function getAllUpcomingForStudent(User $student): \Illuminate\Database\Eloquent\Collection
    {
        $enrolledCourseIds = Enrollment::where('user_id', $student->id)
            ->whereIn('status', ['approved', 'completed'])
            ->pluck('course_id');

        return LiveClass::whereIn('course_id', $enrolledCourseIds)
            ->where(function ($q) {
                $q->where('status', LiveClass::STATUS_SCHEDULED)
                  ->orWhere('status', LiveClass::STATUS_LIVE);
            })
            ->where('scheduled_at', '>=', now()->subHours(2))
            ->orderBy('scheduled_at')
            ->with('course')
            ->get();
    }
}
