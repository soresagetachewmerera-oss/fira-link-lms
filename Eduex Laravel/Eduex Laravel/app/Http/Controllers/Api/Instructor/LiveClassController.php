<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLiveClassRequest;
use App\Models\Course;
use App\Models\LiveClass;
use App\Services\LiveClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Instructor API LiveClassController
 *
 * Provides live class management to the Flutter instructor app.
 *
 * @package App\Http\Controllers\Api\Instructor
 */
class LiveClassController extends Controller
{
    public function __construct(protected LiveClassService $liveClassService) {}

    /**
     * GET /api/instructor/live-classes
     */
    public function index(Request $request): JsonResponse
    {
        $instructor = $request->user();

        $liveClasses = LiveClass::where('instructor_id', $instructor->id)
            ->with(['course', 'lesson'])
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        return response()->json([
            'data'       => $liveClasses->map(fn($lc) => $this->format($lc)),
            'pagination' => [
                'current_page' => $liveClasses->currentPage(),
                'last_page'    => $liveClasses->lastPage(),
                'total'        => $liveClasses->total(),
            ],
        ]);
    }

    /**
     * GET /api/instructor/live-classes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $instructor = $request->user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)
            ->with(['course', 'lesson'])
            ->findOrFail($id);

        return response()->json(['data' => $this->format($liveClass, true)]);
    }

    /**
     * POST /api/instructor/live-classes
     */
    public function store(StoreLiveClassRequest $request): JsonResponse
    {
        $instructor = $request->user();

        // Verify course belongs to instructor
        Course::where('id', $request->course_id)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        $liveClass = $this->liveClassService->schedule($request->validated(), $instructor);

        return response()->json([
            'success' => true,
            'message' => 'Live class scheduled successfully.',
            'data'    => $this->format($liveClass, true),
        ], 201);
    }

    /**
     * PUT /api/instructor/live-classes/{id}
     */
    public function update(StoreLiveClassRequest $request, int $id): JsonResponse
    {
        $instructor = $request->user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        if (!in_array($liveClass->status, [LiveClass::STATUS_SCHEDULED])) {
            return response()->json(['message' => 'Only scheduled classes can be edited.'], 422);
        }

        $liveClass = $this->liveClassService->update($liveClass, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Live class updated.',
            'data'    => $this->format($liveClass, true),
        ]);
    }

    /**
     * DELETE /api/instructor/live-classes/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $instructor = $request->user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        $this->liveClassService->cancel($liveClass);
        $liveClass->delete();

        return response()->json(['success' => true, 'message' => 'Live class cancelled.']);
    }

    /**
     * POST /api/instructor/live-classes/{id}/start
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $instructor = $request->user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        if ($liveClass->status !== LiveClass::STATUS_SCHEDULED) {
            return response()->json(['message' => 'Only scheduled classes can be started.'], 422);
        }

        $this->liveClassService->startSession($liveClass);

        return response()->json([
            'success' => true,
            'message' => 'Session started. Students notified.',
            'data'    => $this->format($liveClass->fresh(), true),
        ]);
    }

    /**
     * POST /api/instructor/live-classes/{id}/end
     */
    public function end(Request $request, int $id): JsonResponse
    {
        $instructor = $request->user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        if ($liveClass->status !== LiveClass::STATUS_LIVE) {
            return response()->json(['message' => 'Only live sessions can be ended.'], 422);
        }

        $request->validate([
            'recording_url' => ['nullable', 'url'],
        ]);

        $this->liveClassService->endSession(
            $liveClass,
            $request->input('recording_url'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Session ended.',
            'data'    => $this->format($liveClass->fresh(), true),
        ]);
    }

    private function format(LiveClass $lc, bool $full = false): array
    {
        $data = [
            'id'                => $lc->id,
            'title'             => $lc->title,
            'course_id'         => $lc->course_id,
            'course_title'      => $lc->course?->title,
            'lesson_id'         => $lc->lesson_id,
            'lesson_title'      => $lc->lesson?->title,
            'join_url'          => $lc->join_url,
            'scheduled_at'      => $lc->scheduled_at->toIso8601String(),
            'duration_minutes'  => $lc->duration_minutes,
            'status'            => $lc->status,
            'minutes_until'     => $lc->minutes_until,
            'is_upcoming'       => $lc->is_upcoming,
            'is_currently_live' => $lc->is_currently_live,
        ];

        if ($full) {
            $data['description']         = $lc->description;
            $data['recording_url']       = $lc->recording_url;
            $data['recording_video_url'] = $lc->recording_video_url;
        }

        return $data;
    }

    /**
     * GET /api/instructor/live-classes/course/{courseId}/lessons
     */
    public function getLessonsForCourse(Request $request, int $courseId): JsonResponse
    {
        $instructor = $request->user();

        $course = Course::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        $lessons = \App\Models\Lesson::whereHas('topic', fn($q) => $q->where('course_id', $course->id))
            ->where('is_live', true)
            ->orderBy('order')
            ->get(['id', 'title']);

        return response()->json(['lessons' => $lessons]);
    }
}
