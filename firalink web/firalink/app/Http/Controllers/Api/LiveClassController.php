<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveClass;
use App\Services\LiveClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Api LiveClassController (Student)
 *
 * Provides live class data to the Flutter student app.
 *
 * @package App\Http\Controllers\Api
 */
class LiveClassController extends Controller
{
    public function __construct(protected LiveClassService $liveClassService) {}

    /**
     * GET /api/live-classes/upcoming
     * Returns the next upcoming live class for the authenticated student.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $student    = $request->user();
        $liveClass  = $this->liveClassService->getUpcomingForStudent($student);

        if (!$liveClass) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $this->formatLiveClass($liveClass)]);
    }

    /**
     * GET /api/live-classes
     * Returns all upcoming live classes for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $student     = $request->user();
        $liveClasses = $this->liveClassService->getAllUpcomingForStudent($student);

        return response()->json([
            'data' => $liveClasses->map(fn($lc) => $this->formatLiveClass($lc))->values(),
        ]);
    }

    /**
     * GET /api/live-classes/{id}
     * Returns a single live class detail including join URL.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $student   = $request->user();
        $liveClass = LiveClass::with('course')->findOrFail($id);

        // Verify student is enrolled in this course
        $isEnrolled = $student->enrollments()
            ->where('course_id', $liveClass->course_id)
            ->whereIn('status', ['approved', 'completed'])
            ->exists();

        if (!$isEnrolled) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        return response()->json(['data' => $this->formatLiveClass($liveClass, true)]);
    }

    /**
     * Format a live class for API response.
     */
    private function formatLiveClass(LiveClass $lc, bool $includeDetails = false): array
    {
        $data = [
            'id'               => $lc->id,
            'title'            => $lc->title,
            'course_id'        => $lc->course_id,
            'course_title'     => $lc->course?->title,
            'course_thumbnail' => $lc->course?->thumbnail,
            'join_url'         => $lc->join_url,
            'scheduled_at'     => $lc->scheduled_at->toIso8601String(),
            'duration_minutes' => $lc->duration_minutes,
            'status'           => $lc->status,
            'minutes_until'    => $lc->minutes_until,
            'is_upcoming'      => $lc->is_upcoming,
            'is_currently_live' => $lc->is_currently_live,
        ];

        if ($includeDetails) {
            $data['description']        = $lc->description;
            $data['recording_url']      = $lc->recording_url;
            $data['recording_video_url'] = $lc->recording_video_url;
            $data['lesson_id']          = $lc->lesson_id;
        }

        return $data;
    }
}
