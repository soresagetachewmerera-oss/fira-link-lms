<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\QuizAttemptAnswer;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StudentCourseController extends Controller
{
    /**
     * Get full course curriculum and user progress
     */
    public function show(Request $request, $courseId)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course or enrollment is not active.'], 403);
        }

        $course = Course::with([
            'topics.lessons',
            'topics.quizzes',
            'topics.assignments',
            'instructor'
        ])->findOrFail($courseId);

        // Check if course is completed
        $isCompleted = $enrollment->status === Enrollment::STATUS_COMPLETED || $this->isCourseCompleted($user, $enrollment, $course);
        if ($isCompleted && $enrollment->status !== Enrollment::STATUS_COMPLETED) {
            $enrollment->update(['status' => Enrollment::STATUS_COMPLETED]);
            $enrollment->refresh();
        }

        // Fetch User Progress
        $userProgress = $user->lessonProgress()
            ->where('enrollment_id', $enrollment->id)
            ->get()
            ->keyBy('lesson_id');

        $userQuizAttempts = $user->quizAttempts()
            ->where('enrollment_id', $enrollment->id)
            ->get()
            ->keyBy('quiz_id');

        $userAssignmentSubmissions = $user->assignmentSubmissions()
            ->where('enrollment_id', $enrollment->id)
            ->get()
            ->keyBy('assignment_id');

        // Calculate Accessibility
        $accessibleItems = [];
        $allItems = $this->getAllCourseItems($course);
        foreach ($allItems as $item) {
            $accessibleItems[$item['type'] . '_' . $item['id']] = $this->isItemAccessible($user, $enrollment, $courseId, $item['type'], $item['id']);
        }

        // Determine Current/Next Item to Resume
        $currentItem = $this->determineCurrentItem($allItems, $accessibleItems, $userProgress, $userQuizAttempts, $userAssignmentSubmissions);

        // Format Topics Response
        $topicsData = [];
        foreach ($course->topics as $topic) {
            $itemsData = [];

            foreach ($topic->lessons as $lesson) {
                $progress = $userProgress[$lesson->id] ?? null;
                $itemsData[] = [
                    'id' => $lesson->id,
                    'type' => 'lesson',
                    'title' => $lesson->title,
                    'duration' => $lesson->formatted_duration, // Ensure accessor exists or format here
                    'is_completed' => $progress && $progress->is_completed,
                    'is_accessible' => $accessibleItems['lesson_' . $lesson->id] ?? false,
                    'is_live' => (bool)$lesson->is_live,
                    'live_class' => $lesson->is_live && $lesson->liveClass ? [
                        'id' => $lesson->liveClass->id,
                        'title' => $lesson->liveClass->title,
                        'status' => $lesson->liveClass->status,
                        'scheduled_at' => $lesson->liveClass->scheduled_at ? $lesson->liveClass->scheduled_at->toIso8601String() : null,
                        'duration_minutes' => $lesson->liveClass->duration_minutes,
                        'recording_url' => $lesson->liveClass->recording_url ?: $lesson->liveClass->recording_video_url,
                    ] : null,
                ];
            }

            foreach ($topic->quizzes as $quiz) {
                $attempt = $userQuizAttempts[$quiz->id] ?? null;
                $itemsData[] = [
                    'id' => $quiz->id,
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'is_completed' => $attempt && $attempt->passed,
                    'is_accessible' => $accessibleItems['quiz_' . $quiz->id] ?? false,
                ];
            }

            foreach ($topic->assignments as $assignment) {
                $submission = $userAssignmentSubmissions[$assignment->id] ?? null;
                $itemsData[] = [
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'is_completed' => $submission !== null,
                    'is_accessible' => $accessibleItems['assignment_' . $assignment->id] ?? false,
                ];
            }

            $topicsData[] = [
                'id' => $topic->id,
                'title' => $topic->title,
                'items' => $itemsData,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'instructor_name' => $course->instructor->name ?? 'Unknown Instructor',
                ],
                'is_completed' => $isCompleted,
                'current_item' => $currentItem,
                'topics' => $topicsData,
            ]
        ]);
    }

    /**
     * Load specific lesson content
     */
    public function loadLesson(Request $request, $courseId, $lessonId)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $lesson = Lesson::with(['topic.course', 'liveClass'])->findOrFail($lessonId);

        if ($lesson->topic->course_id != $courseId) {
            return response()->json(['message' => 'Lesson does not belong to this course'], 403);
        }

        if (!$this->isItemAccessible($user, $enrollment, $courseId, 'lesson', $lessonId)) {
            return response()->json(['message' => 'This lesson is locked. Complete previous items first.'], 403);
        }

        // Track Progress (Last Accessed)
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'is_completed' => false,
                'progress_percentage' => 0,
                'last_accessed_at' => now(),
            ]
        );
        $progress->update(['last_accessed_at' => now()]);

        $videoData = $this->prepareLessonVideoData($lesson);

        return response()->json([
            'success' => true,
            'data' => [
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description, // Or content/notes
                    'duration' => $lesson->duration,
                    'is_live' => (bool)$lesson->is_live,
                    'live_class' => $lesson->is_live && $lesson->liveClass ? [
                        'id' => $lesson->liveClass->id,
                        'title' => $lesson->liveClass->title,
                        'status' => $lesson->liveClass->status,
                        'scheduled_at' => $lesson->liveClass->scheduled_at ? $lesson->liveClass->scheduled_at->toIso8601String() : null,
                        'duration_minutes' => $lesson->liveClass->duration_minutes,
                        'recording_url' => $lesson->liveClass->recording_url ?: $lesson->liveClass->recording_video_url,
                    ] : null,
                ],
                'video' => $videoData,
                'is_completed' => $progress->is_completed,
            ]
        ]);
    }

    /**
     * Mark lesson as complete
     */
    public function markLessonComplete(Request $request, $courseId, $lessonId)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $progress->markComplete(); // Assumes markComplete method exists on model, or use update(['is_completed' => true])

        // Check course completion
        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);
        $courseCompleted = $this->checkAndUpdateCourseCompletion($user, $enrollment, $course);

        // Find Next Item
        $allItems = $this->getAllCourseItems($course);
        $currentIndex = $allItems->search(function ($item) use ($lessonId) {
            return $item['type'] === 'lesson' && $item['id'] == $lessonId;
        });

        $nextItem = null;
        if (!$courseCompleted && $currentIndex !== false && $currentIndex < $allItems->count() - 1) {
            for ($i = $currentIndex + 1; $i < $allItems->count(); $i++) {
                $potentialNext = $allItems[$i];
                // Since this lesson is now complete, the next accessible item should be the immediate next one
                // assuming linear progression.
                if ($this->isItemAccessible($user, $enrollment, $courseId, $potentialNext['type'], $potentialNext['id'])) {
                    $nextItem = [
                        'id' => $potentialNext['id'],
                        'type' => $potentialNext['type'],
                    ];
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'course_completed' => $courseCompleted,
            'next_item' => $nextItem
        ]);
    }

    /**
     * Load specific quiz content
     */
    public function loadQuiz(Request $request, $courseId, $quizId)
    {
        $user = $request->user();
        $forceRetake = $request->input('retake', false);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $quiz = Quiz::with(['questions', 'topic.course'])
            ->whereHas('topic', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->find($quizId);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found or does not belong to this course'], 404);
        }

        if (!$this->isItemAccessible($user, $enrollment, $courseId, 'quiz', $quizId)) {
            return response()->json(['message' => 'This quiz is locked. Complete previous items first.'], 403);
        }

        $previousAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->where('enrollment_id', $enrollment->id)
            ->with(['answers'])
            ->latest()
            ->first();

        // If passed and not force retake, return results
        if ($previousAttempt && $previousAttempt->passed && !$forceRetake) {
            return response()->json([
                'success' => true,
                'data' => [
                    'quiz' => $quiz,
                    'is_completed' => true,
                    'last_attempt' => $previousAttempt,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'quiz' => $quiz,
                'is_completed' => false,
                'last_attempt' => $previousAttempt, // Might be failed attempt
            ]
        ]);
    }

    /**
     * Submit Quiz
     */
    public function submitQuiz(Request $request, $courseId, $quizId)
    {
        $user = $request->user();
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $quiz = Quiz::with('questions')->findOrFail($quizId);

        DB::beginTransaction();
        try {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quizId,
                'enrollment_id' => $enrollment->id,
                'started_at' => now(), // Should ideally come from request or session start
                'completed_at' => now(),
                'time_taken' => $request->input('time_taken', 0),
                'score' => 0,
                'passed' => false,
            ]);

            $correctCount = 0;
            $totalQuestions = $quiz->questions->count();
            $answers = $request->input('answers', []);

            foreach ($answers as $answerData) {
                // answerData: { question_id: 1, selected_answer: 2 }
                $question = $quiz->questions->find($answerData['question_id']);
                if (!$question)
                    continue;

                $selectedAnswer = (int) $answerData['selected_answer'];
                $isCorrect = $question->correct_answer === $selectedAnswer;

                if ($isCorrect) {
                    $correctCount++;
                }

                QuizAttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_answer' => $selectedAnswer,
                    'is_correct' => $isCorrect,
                ]);
            }

            $score = $totalQuestions > 0 ? (int) round(($correctCount / $totalQuestions) * 100) : 0;
            $passed = $score >= $quiz->passing_score;

            $attempt->update([
                'score' => $score,
                'passed' => $passed,
            ]);

            DB::commit();

            // Refresh course progress logic
            $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);
            $this->checkAndUpdateCourseCompletion($user, $enrollment, $course);

            // Determine Next Item
            $nextItem = null;
            if ($passed) {
                $allItems = $this->getAllCourseItems($course);
                $currentIndex = $allItems->search(function ($item) use ($quizId) {
                    return $item['type'] === 'quiz' && $item['id'] == $quizId;
                });
                if ($currentIndex !== false && $currentIndex < $allItems->count() - 1) {
                    $nextItem = $allItems[$currentIndex + 1]; // Simplify for now
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'passed' => $passed,
                    'score' => $score,
                    'attempt' => $attempt,
                    'next_item' => $nextItem,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Load Assignment
     */
    public function loadAssignment(Request $request, $courseId, $assignmentId)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $assignment = Assignment::with(['topic.course'])
            ->whereHas('topic', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->find($assignmentId);

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        if (!$this->isItemAccessible($user, $enrollment, $courseId, 'assignment', $assignmentId)) {
            return response()->json(['message' => 'Assignment is locked.'], 403);
        }

        $submission = AssignmentSubmission::where('user_id', $user->id)
            ->where('assignment_id', $assignmentId)
            ->where('enrollment_id', $enrollment->id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment,
                'submission' => $submission,
                'is_completed' => $submission !== null,
            ]
        ]);
    }

    /**
     * Submit Assignment
     */
    public function submitAssignment(Request $request, $courseId, $assignmentId)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'submission_text' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        // Check availability logic again if strict

        DB::beginTransaction();
        try {
            $submission = AssignmentSubmission::create([
                'user_id' => $user->id,
                'assignment_id' => $assignmentId,
                'enrollment_id' => $enrollment->id,
                'submission_text' => $request->input('submission_text'),
                'submitted_at' => now(),
                'status' => 'pending', // Use constant if available
            ]);

            // Handle File Upload (Simulated or implemented if FileUploadService available)
            // For now, assume simplified or no file, or just store path if simple.
            // In web controller, $this->fileUploadService is used. 
            // We can implement basic storage here or ignore file for this MVP if strict on services.

            // Basic File Upload Implementation:
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('assignments', 'public');

                $submission->files()->create([
                    'file_path' => $path,
                    'original_filename' => $request->file('file')->getClientOriginalName(),
                    'file_size' => $request->file('file')->getSize(),
                    'mime_type' => $request->file('file')->getClientMimeType(),
                ]);
            }

            DB::commit();

            // Update Completion
            $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);
            $this->checkAndUpdateCourseCompletion($user, $enrollment, $course);

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully',
                'data' => [
                    'submission' => $submission
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Get all user assignments across courses
     */
    public function myAssignments(Request $request)
    {
        $user = $request->user();

        $courseIds = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->pluck('course_id');

        $assignments = Assignment::whereHas('topic', function ($query) use ($courseIds) {
            $query->whereIn('course_id', $courseIds);
        })
            ->with([
                'topic.course',
                'submissions' => function ($query) use ($user) {
                    $query->where('user_id', $user->id)->latest();
                }
            ])
            ->get();

        $data = $assignments->map(function ($assignment) {
            $submission = $assignment->submissions->first();
            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'course_title' => $assignment->topic->course->title,
                'due_date' => $assignment->created_at->addDays(7)->format('M d, Y'), // Use placeholder logic for due date
                'status' => $submission ? $submission->status : 'pending',
                'grade' => $submission ? $submission->grade : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all user quiz attempts
     */
    public function myQuizzes(Request $request)
    {
        $user = $request->user();

        $attempts = QuizAttempt::where('user_id', $user->id)
            ->with(['quiz.topic.course'])
            ->latest()
            ->get();

        $data = $attempts->map(function ($attempt) {
            return [
                'id' => $attempt->id,
                'title' => $attempt->quiz->title,
                'course_title' => $attempt->quiz->topic->course->title,
                'score' => $attempt->score,
                'max_score' => 100,
                'passed' => $attempt->passed,
                'date' => $attempt->completed_at ? $attempt->completed_at->format('M d, Y') : $attempt->created_at->format('M d, Y'),
                'attempts' => QuizAttempt::where('user_id', $attempt->user_id)->where('quiz_id', $attempt->quiz_id)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /*                               Helper Methods                               */
    /* -------------------------------------------------------------------------- */

    private function prepareLessonVideoData($lesson)
    {
        $videoData = [
            'type' => $lesson->video_type, // 'url', 'upload', 'live'
            'url' => null,
            'is_youtube' => false,
            'is_vimeo' => false,
            'video_id' => null, // youtube or vimeo id
        ];

        if ($lesson->is_live) {
            $videoData['type'] = 'live';
            $liveClass = $lesson->liveClass;
            if ($liveClass && $liveClass->status === 'ended') {
                if ($liveClass->recording_url) {
                    $videoData['url'] = $liveClass->recording_url;
                    $videoData['type'] = 'url';
                    if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $liveClass->recording_url, $matches)) {
                        $videoData['is_youtube'] = true;
                        $videoData['video_id'] = $matches[2];
                    } elseif (preg_match('/vimeo\.com\/(\d+)/', $liveClass->recording_url, $matches)) {
                        $videoData['is_vimeo'] = true;
                        $videoData['video_id'] = $matches[1];
                    }
                } elseif ($liveClass->recording_video_path) {
                    $videoPath = ltrim($liveClass->recording_video_path, '/');
                    $videoData['url'] = url('api/video/stream') . '?path=' . urlencode($videoPath);
                    $videoData['type'] = 'upload';
                }
            }
        } elseif ($lesson->video_type === 'url' && $lesson->video_url) {
            $videoData['url'] = $lesson->video_url;

            if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $lesson->video_url, $matches)) {
                $videoData['is_youtube'] = true;
                $videoData['video_id'] = $matches[2];
            } elseif (preg_match('/vimeo\.com\/(\d+)/', $lesson->video_url, $matches)) {
                $videoData['is_vimeo'] = true;
                $videoData['video_id'] = $matches[1];
            }
        } elseif ($lesson->video_type === 'upload' && $lesson->video_path) {
            $videoPath = ltrim($lesson->video_path, '/');
            // Use streaming endpoint for hosted videos to support iOS/Range requests
            $videoData['url'] = url('api/video/stream') . '?path=' . urlencode($videoPath);
        }

        return $videoData;
    }

    private function getAllCourseItems($course)
    {
        $items = collect();

        foreach ($course->topics as $topic) {
            foreach ($topic->lessons->sortBy('order') as $lesson) {
                $items->push([
                    'id' => $lesson->id,
                    'type' => 'lesson',
                    'order' => $lesson->order,
                    'topic_order' => $topic->order ?? 0,
                ]);
            }
            foreach ($topic->quizzes->sortBy('order') as $quiz) {
                $items->push([
                    'id' => $quiz->id,
                    'type' => 'quiz',
                    'order' => $quiz->order,
                    'topic_order' => $topic->order ?? 0,
                ]);
            }
            foreach ($topic->assignments->sortBy('order') as $assignment) {
                $items->push([
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'order' => $assignment->order,
                    'topic_order' => $topic->order ?? 0,
                ]);
            }
        }

        // Sort by topic order then item order
        return $items->sortBy([
            ['topic_order', 'asc'],
            ['order', 'asc'],
        ])->values();
    }

    private function isItemAccessible($user, $enrollment, $courseId, $type, $itemId)
    {
        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->find($courseId);
        // optimization: pass $course if available to avoid refetching, but stateless API calls might easier just fetch.
        // For performance, we could pass course structure if not huge.
        // Here kept simple with fetch.

        $allItems = $this->getAllCourseItems($course);

        $currentIndex = $allItems->search(function ($item) use ($type, $itemId) {
            return $item['type'] === $type && $item['id'] == $itemId;
        });

        if ($currentIndex === 0 || $currentIndex === false) {
            return $currentIndex !== false; // First item is always accessible if it exists
        }

        // Check if all previous items are completed
        for ($i = 0; $i < $currentIndex; $i++) {
            $previousItem = $allItems[$i];
            if (!$this->isItemCompleted($user, $enrollment, $previousItem['type'], $previousItem['id'])) {
                return false;
            }
        }

        return true;
    }

    private function isItemCompleted($user, $enrollment, $type, $itemId)
    {
        if ($type === 'lesson') {
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $itemId)
                ->where('enrollment_id', $enrollment->id)
                ->first();
            return $progress && $progress->is_completed;
        } elseif ($type === 'quiz') {
            $attempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $itemId)
                ->where('enrollment_id', $enrollment->id)
                ->where('passed', true)
                ->first();
            return $attempt !== null;
        } elseif ($type === 'assignment') {
            $submission = AssignmentSubmission::where('user_id', $user->id)
                ->where('assignment_id', $itemId)
                ->where('enrollment_id', $enrollment->id)
                ->first();
            return $submission !== null;
        }
        return false;
    }

    private function isCourseCompleted($user, $enrollment, $course)
    {
        $allItems = $this->getAllCourseItems($course);
        if ($allItems->isEmpty())
            return false;

        foreach ($allItems as $item) {
            if (!$this->isItemCompleted($user, $enrollment, $item['type'], $item['id'])) {
                return false;
            }
        }
        return true;
    }

    private function checkAndUpdateCourseCompletion($user, $enrollment, $course)
    {
        if ($enrollment->status !== Enrollment::STATUS_COMPLETED && $this->isCourseCompleted($user, $enrollment, $course)) {
            $enrollment->update(['status' => Enrollment::STATUS_COMPLETED]);
            $enrollment->refresh();
            return true;
        }
        return false;
    }

    private function determineCurrentItem($allItems, $accessibleItems, $userProgress, $userQuizAttempts, $userAssignmentSubmissions)
    {
        // Default to first item
        if ($allItems->isEmpty())
            return null;

        // Find the first accessible item that is NOT completed
        // Or the last accessed item if we have tracking?
        // Let's go with the Last Accessed logic or First Uncompleted logic.
        // First Uncompleted is standard.

        foreach ($allItems as $item) {
            $key = $item['type'] . '_' . $item['id'];
            $isAccessible = $accessibleItems[$key] ?? false;

            if ($isAccessible) {
                // Check if completed
                $isCompleted = false;
                // Can optimize by passing in the collections
                // For now, doing quick lookups in the collections passed in if keys match
                // We passed in keyed collections $userProgress etc.

                if ($item['type'] === 'lesson') {
                    $isCompleted = isset($userProgress[$item['id']]) && $userProgress[$item['id']]->is_completed;
                } elseif ($item['type'] === 'quiz') {
                    $isCompleted = isset($userQuizAttempts[$item['id']]) && $userQuizAttempts[$item['id']]->passed;
                } elseif ($item['type'] === 'assignment') {
                    $isCompleted = isset($userAssignmentSubmissions[$item['id']]);
                }

                if (!$isCompleted) {
                    return [
                        'id' => $item['id'],
                        'type' => $item['type']
                    ];
                }
            }
        }

        // If all completed, return the last item? Or null (course complete)
        // Return null or maybe the first item to review.
        return [
            'id' => $allItems->first()['id'],
            'type' => $allItems->first()['type']
        ];
    }
}
