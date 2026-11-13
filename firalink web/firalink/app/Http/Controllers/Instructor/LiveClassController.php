<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLiveClassRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Services\FileUploadService;
use App\Services\LiveClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Instructor LiveClassController (Web)
 *
 * Handles scheduling, managing, and ending live class sessions.
 *
 * @package App\Http\Controllers\Instructor
 */
class LiveClassController extends Controller
{
    public function __construct(
        protected LiveClassService $liveClassService,
        protected FileUploadService $fileUploadService,
    ) {}

    /**
     * List all live classes for the authenticated instructor.
     */
    public function index()
    {
        $instructor = Auth::user();

        $liveClasses = LiveClass::where('instructor_id', $instructor->id)
            ->with(['course', 'lesson'])
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        return view('instructor.live-classes.index', compact('liveClasses'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $instructor = Auth::user();

        $courses = Course::where('instructor_id', $instructor->id)
            ->whereIn('status', ['published', 'pending'])
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('instructor.live-classes.create', compact('courses'));
    }

    /**
     * Get lessons for a course (AJAX).
     */
    public function getLessonsForCourse(Request $request)
    {
        $instructor = Auth::user();

        $course = Course::where('id', $request->course_id)
            ->where('instructor_id', $instructor->id)
            ->first();

        if (!$course) {
            return response()->json(['lessons' => []]);
        }

        $lessons = Lesson::whereHas('topic', fn($q) => $q->where('course_id', $course->id))
            ->where('is_live', true)
            ->orderBy('order')
            ->get(['id', 'title']);

        return response()->json(['lessons' => $lessons]);
    }

    /**
     * Store a new live class.
     */
    public function store(StoreLiveClassRequest $request): RedirectResponse
    {
        $instructor = Auth::user();

        // Ensure the course belongs to this instructor
        $course = Course::where('id', $request->course_id)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        $liveClass = $this->liveClassService->schedule($request->validated(), $instructor);

        return redirect()
            ->route('instructor.live-classes.index')
            ->with('success', "Live class \"{$liveClass->title}\" scheduled successfully!");
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $instructor = Auth::user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        $courses = Course::where('instructor_id', $instructor->id)
            ->whereIn('status', ['published', 'pending'])
            ->orderBy('title')
            ->get(['id', 'title']);

        $lessons = Lesson::whereHas('topic', fn($q) => $q->where('course_id', $liveClass->course_id))
            ->where('is_live', true)
            ->get(['id', 'title']);

        return view('instructor.live-classes.edit', compact('liveClass', 'courses', 'lessons'));
    }

    /**
     * Update a live class.
     */
    public function update(StoreLiveClassRequest $request, int $id): RedirectResponse
    {
        $instructor = Auth::user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        $this->liveClassService->update($liveClass, $request->validated());

        return redirect()
            ->route('instructor.live-classes.index')
            ->with('success', 'Live class updated successfully!');
    }

    /**
     * Cancel / delete a live class.
     */
    public function destroy(int $id): RedirectResponse
    {
        $instructor = Auth::user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        $this->liveClassService->cancel($liveClass);
        $liveClass->delete();

        return redirect()
            ->route('instructor.live-classes.index')
            ->with('success', 'Live class cancelled and removed.');
    }

    /**
     * Start a live session — marks status to 'live' and notifies students.
     */
    public function startSession(int $id): RedirectResponse
    {
        $instructor = Auth::user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        if ($liveClass->status !== LiveClass::STATUS_SCHEDULED) {
            return back()->with('error', 'Only scheduled classes can be started.');
        }

        $this->liveClassService->startSession($liveClass);

        return redirect()
            ->route('instructor.live-classes.index')
            ->with('success', "Live class \"{$liveClass->title}\" is now live! Students have been notified.");
    }

    /**
     * End a live session and optionally attach recording.
     */
    public function endSession(Request $request, int $id): RedirectResponse
    {
        $instructor = Auth::user();
        $liveClass  = LiveClass::where('instructor_id', $instructor->id)->findOrFail($id);

        if ($liveClass->status !== LiveClass::STATUS_LIVE) {
            return back()->with('error', 'Only live sessions can be ended.');
        }

        $request->validate([
            'recording_url'  => ['nullable', 'url'],
            'recording_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,webm', 'max:512000'],
        ]);

        $this->liveClassService->endSession(
            $liveClass,
            $request->input('recording_url'),
            $request->file('recording_file')
        );

        return redirect()
            ->route('instructor.live-classes.index')
            ->with('success', 'Live session ended. Recording attached if provided.');
    }
}
