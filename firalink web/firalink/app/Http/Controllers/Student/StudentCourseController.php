<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAssignmentRequest;
use App\Http\Requests\SubmitQuizRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\CourseTopic;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

/**
 * Student Course Controller
 *
 * This controller handles the student course functionality.
 *
 * @package App\Http\Controllers\Student
 */
class StudentCourseController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function show($courseId)
    {
        $user = $this->currentUser();
        
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $course = Course::with([
            'topics.lessons.liveClass',
            'topics.quizzes.questions',
            'topics.assignments.files',
            'instructor'
        ])->findOrFail($courseId);

        $isCompleted = $enrollment->status === Enrollment::STATUS_COMPLETED || $this->isCourseCompleted($user, $enrollment, $course);
        if ($isCompleted && $enrollment->status !== Enrollment::STATUS_COMPLETED) {
            $enrollment->update(['status' => Enrollment::STATUS_COMPLETED]);
            $enrollment->refresh();
        }

        $firstLesson = $course->topics->flatMap->lessons->sortBy('order')->first();
        $firstQuiz = $course->topics->flatMap->quizzes->sortBy('order')->first();
        $firstAssignment = $course->topics->flatMap->assignments->sortBy('order')->first();
        
        $firstQuizPassed = false;
        if ($firstQuiz) {
            $firstQuizAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $firstQuiz->id)
                ->where('enrollment_id', $enrollment->id)
                ->where('passed', true)
                ->latest()
                ->first();
            $firstQuizPassed = $firstQuizAttempt !== null;
        }
        
        $currentItem = $firstLesson ?? $firstQuiz ?? $firstAssignment;
        $currentItemType = $firstLesson ? 'lesson' : ($firstQuiz ? 'quiz' : 'assignment');

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

        $accessibleItems = [];
        $allItems = $this->getAllCourseItems($course);
        foreach ($allItems as $index => $item) {
            $accessibleItems[$item['type'] . '_' . $item['id']] = $this->isItemAccessible($user, $enrollment, $courseId, $item['type'], $item['id']);
        }

        $topicsData = [];
        foreach ($course->topics as $index => $topic) {
            $progressStats = $topic->getProgressStats($userProgress, $userQuizAttempts, $userAssignmentSubmissions);
            
            $itemsData = [];
            
            foreach ($topic->lessons as $lesson) {
                $progress = $userProgress[$lesson->id] ?? null;
                $itemsData[] = [
                    'id' => $lesson->id,
                    'type' => 'lesson',
                    'title' => $lesson->title,
                    'duration_formatted' => $lesson->formatted_duration,
                    'is_completed' => $progress && $progress->is_completed,
                    'is_active' => $currentItemType === 'lesson' && $currentItem->id == $lesson->id,
                    'is_accessible' => $accessibleItems['lesson_' . $lesson->id] ?? false,
                    'is_live' => (bool) $lesson->is_live,
                    'live_status' => $lesson->liveClass?->status,
                ];
            }
            
            foreach ($topic->quizzes as $quiz) {
                $attempt = $userQuizAttempts[$quiz->id] ?? null;
                $itemsData[] = [
                    'id' => $quiz->id,
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'duration_formatted' => '',
                    'is_completed' => $attempt && $attempt->passed,
                    'is_active' => $currentItemType === 'quiz' && $currentItem->id == $quiz->id,
                    'is_accessible' => $accessibleItems['quiz_' . $quiz->id] ?? false,
                ];
            }
            
            foreach ($topic->assignments as $assignment) {
                $submission = $userAssignmentSubmissions[$assignment->id] ?? null;
                $itemsData[] = [
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'duration_formatted' => '',
                    'is_completed' => $submission !== null,
                    'is_active' => $currentItemType === 'assignment' && $currentItem->id == $assignment->id,
                    'is_accessible' => $accessibleItems['assignment_' . $assignment->id] ?? false,
                ];
            }
            
            $topicsData[] = [
                'topic' => $topic,
                'index' => $index,
                'progress' => $progressStats,
                'items' => $itemsData,
            ];
        }

        $currentItemDurationFormatted = '';
        $initialLessonVideoData = null;
        $initialQuizPassed = false;
        $initialQuizResultsData = null;
        
        if ($currentItemType === 'lesson' && $currentItem instanceof Lesson) {
            $currentItemDurationFormatted = $currentItem->formatted_duration;
            $initialLessonVideoData = $this->prepareLessonVideoData($currentItem);
        } elseif ($currentItemType === 'quiz' && $currentItem instanceof Quiz) {
            $quizAttempt = $userQuizAttempts[$currentItem->id] ?? null;
            if ($quizAttempt && $quizAttempt->passed) {
                $initialQuizPassed = true;
                $initialQuizResultsData = $this->prepareQuizResultsData($quizAttempt->load('answers.question'), $currentItem);
            }
        }

        return view('student.course-access', compact(
            'course',
            'enrollment',
            'currentItem',
            'currentItemType',
            'currentItemDurationFormatted',
            'userProgress',
            'userQuizAttempts',
            'userAssignmentSubmissions',
            'accessibleItems',
            'topicsData',
            'initialLessonVideoData',
            'initialQuizPassed',
            'initialQuizResultsData',
            'isCompleted'
        ));
    }

    public function loadLesson(Request $request)
    {
        $user = $this->currentUser();
        $lessonId = $request->input('lesson_id');
        $courseId = $request->input('course_id');

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $lesson = Lesson::with(['topic.course', 'liveClass'])->findOrFail($lessonId);

        if ($lesson->topic->course_id != $courseId) {
            return response()->json(['error' => 'Lesson does not belong to this course'], 403);
        }

        if (!$this->isItemAccessible($user, $enrollment, $courseId, 'lesson', $lessonId)) {
            return response()->json([
                'success' => false,
                'error' => 'Please complete the previous lessons before accessing this one.',
                'locked' => true,
            ], 403);
        }

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
        
        $html = view('student.course-access.partials.lesson-content', [
            'lesson' => $lesson,
            'progress' => $progress,
            'videoData' => $videoData,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'lesson_id' => $lesson->id,
            'type' => 'lesson',
            'title' => $lesson->title,
            'duration' => $lesson->duration,
        ]);
    }

    public function loadQuiz(Request $request, $courseId, $quizId)
    {
        $user = $this->currentUser();
        $forceRetake = $request->input('retake', false);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $quiz = Quiz::with(['questions', 'topic.course'])
            ->whereHas('topic', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->find($quizId);

        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found or does not belong to this course'], 404);
        }

        if (!$quiz->topic || $quiz->topic->course_id != $courseId) {
            return response()->json(['error' => 'Quiz does not belong to this course'], 403);
        }

        if (!$this->isItemAccessible($user, $enrollment, $courseId, 'quiz', $quizId)) {
            return response()->json([
                'success' => false,
                'error' => 'Please complete the previous lessons/quizzes/assignments before accessing this quiz.',
                'locked' => true,
            ], 403);
        }

        $previousAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->where('enrollment_id', $enrollment->id)
            ->latest()
            ->first();

        if ($previousAttempt && $previousAttempt->passed && !$forceRetake) {
            $resultsData = $this->prepareQuizResultsData($previousAttempt, $quiz);
            $html = view('student.course-access.partials.quiz-results', $resultsData)->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'quiz_id' => $quiz->id,
                'type' => 'quiz',
                'title' => $quiz->title,
                'is_completed' => true,
                'attempt_id' => $previousAttempt->id,
            ]);
        }

        $html = view('student.course-access.partials.quiz-content', compact('quiz', 'previousAttempt'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'quiz_id' => $quiz->id,
            'type' => 'quiz',
            'title' => $quiz->title,
            'is_completed' => false,
        ]);
    }

    public function loadAssignment(Request $request, $courseId, $assignmentId)
    {
        $user = $this->currentUser();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $assignment = Assignment::with(['files', 'topic.course'])
            ->whereHas('topic', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->find($assignmentId);

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found or does not belong to this course'], 404);
        }

        if (!$assignment->topic || $assignment->topic->course_id != $courseId) {
            return response()->json(['error' => 'Assignment does not belong to this course'], 403);
        }

        if (!$this->isItemAccessible($user, $enrollment, $courseId, 'assignment', $assignmentId)) {
            return response()->json([
                'success' => false,
                'error' => 'Please complete the previous lessons/quizzes/assignments before accessing this assignment.',
                'locked' => true,
            ], 403);
        }

        $submission = AssignmentSubmission::where('user_id', $user->id)
            ->where('assignment_id', $assignmentId)
            ->where('enrollment_id', $enrollment->id)
            ->with('files')
            ->latest()
            ->first();

        $statusLabels = [
            AssignmentSubmission::STATUS_PENDING => 'Pending',
            AssignmentSubmission::STATUS_GRADED => 'Graded',
            AssignmentSubmission::STATUS_RETURNED => 'Returned',
        ];

        $statusClasses = [
            AssignmentSubmission::STATUS_PENDING => 'pending',
            AssignmentSubmission::STATUS_GRADED => 'graded',
            AssignmentSubmission::STATUS_RETURNED => 'returned',
        ];

        $submissionData = null;
        if ($submission) {
            $submissionData = [
                'status_label' => $statusLabels[$submission->status] ?? ucfirst($submission->status),
                'status_class' => $statusClasses[$submission->status] ?? 'pending',
                'is_graded' => $submission->status === AssignmentSubmission::STATUS_GRADED,
                'can_resubmit' => $submission->status === AssignmentSubmission::STATUS_RETURNED,
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : null,
            ];
        }

        $html = view('student.course-access.partials.assignment-content', [
            'assignment' => $assignment,
            'submission' => $submission,
            'submissionData' => $submissionData,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'assignment_id' => $assignment->id,
            'type' => 'assignment',
            'title' => $assignment->title,
        ]);
    }

    public function markComplete(Request $request)
    {
        $user = $this->currentUser();
        $lessonId = $request->input('lesson_id');
        $courseId = $request->input('course_id');

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $progress->markComplete();

        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);
        $courseCompleted = $this->checkAndUpdateCourseCompletion($user, $enrollment, $course);
        
        $allItems = $this->getAllCourseItems($course);
        
        $currentIndex = $allItems->search(function($item) use ($lessonId) {
            return $item['type'] === 'lesson' && $item['id'] == $lessonId;
        });

        $nextItem = null;
        if (!$courseCompleted && $currentIndex !== false && $currentIndex < $allItems->count() - 1) {
            for ($i = $currentIndex + 1; $i < $allItems->count(); $i++) {
                $potentialNext = $allItems[$i];
                if ($this->isItemAccessible($user, $enrollment, $courseId, $potentialNext['type'], $potentialNext['id'])) {
                    $nextItem = [
                        'item_id' => $potentialNext['id'],
                        'type' => $potentialNext['type'],
                    ];
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as complete',
            'next_item' => $nextItem,
            'course_completed' => $courseCompleted,
        ]);
    }

    public function updateProgress(Request $request)
    {
        $user = $this->currentUser();
        $lessonId = $request->input('lesson_id');
        $percentage = (int) $request->input('percentage', 0);
        $courseId = $request->input('course_id');

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'is_completed' => false,
                'progress_percentage' => 0,
            ]
        );

        $progress->updateProgress($percentage);

        if ($percentage >= 100) {
            $progress->markComplete();
        }

        return response()->json([
            'success' => true,
            'progress_percentage' => $progress->progress_percentage,
            'is_completed' => $progress->is_completed,
        ]);
    }

    public function submitQuiz(SubmitQuizRequest $request)
    {
        $user = $this->currentUser();
        $quizId = $request->input('quiz_id');

        $enrollment = Enrollment::where('user_id', $user->id)
            ->whereHas('course.topics.quizzes', function ($query) use ($quizId) {
                $query->where('quizzes.id', $quizId);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $quiz = Quiz::with('questions')->findOrFail($quizId);

        DB::beginTransaction();
        try {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quizId,
                'enrollment_id' => $enrollment->id,
                'started_at' => now(),
                'completed_at' => now(),
                'time_taken' => $request->input('time_taken'),
                'score' => 0,
                'passed' => false,
            ]);

            $correctCount = 0;
            $totalQuestions = $quiz->questions->count();

            foreach ($request->input('answers', []) as $answerData) {
                $question = $quiz->questions->find($answerData['question_id']);
                if (!$question) {
                    continue;
                }

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

            $courseCompleted = false;
            $nextItem = null;
            if ($passed) {
                $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($enrollment->course_id);
                $courseCompleted = $this->checkAndUpdateCourseCompletion($user, $enrollment, $course);
                
                $allItems = $this->getAllCourseItems($course);
                
                $currentIndex = $allItems->search(function($item) use ($quizId) {
                    return $item['type'] === 'quiz' && $item['id'] == $quizId;
                });

                if (!$courseCompleted && $currentIndex !== false && $currentIndex < $allItems->count() - 1) {
                    for ($i = $currentIndex + 1; $i < $allItems->count(); $i++) {
                        $potentialNext = $allItems[$i];
                        if ($this->isItemAccessible($user, $enrollment, $enrollment->course_id, $potentialNext['type'], $potentialNext['id'])) {
                            $nextItem = [
                                'item_id' => $potentialNext['id'],
                                'type' => $potentialNext['type'],
                            ];
                            break;
                        }
                    }
                }
            }

            $resultsData = $this->prepareQuizResultsData($attempt->load('answers.question'), $quiz);
            $html = view('student.course-access.partials.quiz-results', $resultsData)->render();

            return response()->json([
                'success' => true,
                'message' => 'Quiz submitted successfully',
                'html' => $html,
                'score' => $score,
                'passed' => $passed,
                'quiz_id' => $quizId,
                'attempt_id' => $attempt->id,
                'next_item' => $nextItem,
                'course_completed' => $courseCompleted,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quiz: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submitAssignment(SubmitAssignmentRequest $request)
    {
        $user = $this->currentUser();
        $assignmentId = $request->input('assignment_id');

        $enrollment = Enrollment::where('user_id', $user->id)
            ->whereHas('course.topics.assignments', function ($query) use ($assignmentId) {
                $query->where('assignments.id', $assignmentId);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $submission = AssignmentSubmission::create([
                'user_id' => $user->id,
                'assignment_id' => $assignmentId,
                'enrollment_id' => $enrollment->id,
                'submission_text' => $request->input('submission_text'),
                'submitted_at' => now(),
                'status' => AssignmentSubmission::STATUS_PENDING,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $fileData = $this->fileUploadService->uploadAssignmentSubmissionFile($file);
                    
                    $submission->files()->create($fileData);
                }
            }

            DB::commit();

            $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($enrollment->course_id);
            $courseCompleted = $this->checkAndUpdateCourseCompletion($user, $enrollment, $course);
            
            $allItems = $this->getAllCourseItems($course);
            
            $currentIndex = $allItems->search(function($item) use ($assignmentId) {
                return $item['type'] === 'assignment' && $item['id'] == $assignmentId;
            });

            $nextItem = null;
            if (!$courseCompleted && $currentIndex !== false && $currentIndex < $allItems->count() - 1) {
                for ($i = $currentIndex + 1; $i < $allItems->count(); $i++) {
                    $potentialNext = $allItems[$i];
                    if ($this->isItemAccessible($user, $enrollment, $enrollment->course_id, $potentialNext['type'], $potentialNext['id'])) {
                        $nextItem = [
                            'item_id' => $potentialNext['id'],
                            'type' => $potentialNext['type'],
                        ];
                        break;
                    }
                }
            }

            $html = view('student.course-access.partials.assignment-submission', [
                'submission' => $submission->load('files'),
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully',
                'html' => $html,
                'next_item' => $nextItem,
                'course_completed' => $courseCompleted,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit assignment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getNextItem(Request $request, $courseId, $itemId)
    {
        $type = $request->input('type', 'lesson');
        $user = $this->currentUser();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);

        $allItems = $this->getAllCourseItems($course);

        $currentIndex = $allItems->search(function($item) use ($type, $itemId) {
            return $item['type'] === $type && $item['id'] == $itemId;
        });

        if ($currentIndex === false) {
            return response()->json([
                'success' => false,
                'message' => 'Current item not found',
            ]);
        }

        for ($i = $currentIndex + 1; $i < $allItems->count(); $i++) {
            $nextItem = $allItems[$i];
            
            if ($this->isItemAccessible($user, $enrollment, $courseId, $nextItem['type'], $nextItem['id'])) {
                return response()->json([
                    'success' => true,
                    'item_id' => $nextItem['id'],
                    'type' => $nextItem['type'],
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No next accessible item found',
        ]);
    }

    public function getPreviousItem(Request $request, $courseId, $itemId)
    {
        $type = $request->input('type', 'lesson');
        $user = $this->currentUser();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);

        $allItems = $this->getAllCourseItems($course);

        $currentIndex = $allItems->search(function($item) use ($type, $itemId) {
            return $item['type'] === $type && $item['id'] == $itemId;
        });

        if ($currentIndex === false || $currentIndex === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No previous item found',
            ]);
        }

        $previousItem = $allItems[$currentIndex - 1];
        return response()->json([
            'success' => true,
            'item_id' => $previousItem['id'],
            'type' => $previousItem['type'],
        ]);
    }

    public function viewQuizResults($courseId, $attemptId)
    {
        $user = $this->currentUser();
        
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();
        
        $attempt = QuizAttempt::with(['quiz.questions', 'answers.question'])
            ->where('user_id', $user->id)
            ->where('enrollment_id', $enrollment->id)
            ->findOrFail($attemptId);

        $resultsData = $this->prepareQuizResultsData($attempt, $attempt->quiz);
        $html = view('student.course-access.partials.quiz-results', $resultsData)->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Prepare quiz results data for view
     */
    private function prepareQuizResultsData($attempt, $quiz)
    {
        $questionsData = [];
        
        foreach ($quiz->questions as $index => $question) {
            $answer = $attempt->answers->where('question_id', $question->id)->first();
            $isCorrect = $answer && $answer->is_correct;
            
            $optionsData = [];
            foreach ($question->options as $optionIndex => $option) {
                $optionsData[] = [
                    'index' => $optionIndex,
                    'text' => $option,
                    'is_correct' => $optionIndex == $question->correct_answer,
                    'is_selected' => $optionIndex == ($answer->selected_answer ?? null),
                    'is_wrong' => $optionIndex == ($answer->selected_answer ?? null) && !$isCorrect,
                ];
            }
            
            $questionsData[] = [
                'question' => $question,
                'answer' => $answer,
                'is_correct' => $isCorrect,
                'index' => $index,
                'options' => $optionsData,
            ];
        }
        
        return [
            'attempt' => $attempt,
            'quiz' => $quiz,
            'questionsData' => $questionsData,
            'correctAnswersCount' => $attempt->answers->where('is_correct', true)->count(),
        ];
    }

    /**
     * Prepare lesson video data for view
     */
    private function prepareLessonVideoData($lesson)
    {
        $videoData = [
            'type' => $lesson->video_type,
            'url' => null,
            'embed_url' => null,
            'is_youtube' => false,
            'is_vimeo' => false,
            'youtube_id' => null,
            'vimeo_id' => null,
            'is_live' => (bool) $lesson->is_live,
            'live_class' => null,
        ];

        // If this is a live lesson, attach live class data
        if ($lesson->is_live && $lesson->liveClass) {
            $lc = $lesson->liveClass;
            $videoData['live_class'] = [
                'id' => $lc->id,
                'status' => $lc->status,
                'title' => $lc->title,
                'join_url' => $lc->join_url,
                'scheduled_at' => $lc->scheduled_at?->toIso8601String(),
                'scheduled_at_formatted' => $lc->scheduled_at?->format('M d, Y · h:i A'),
                'duration_minutes' => $lc->duration_minutes,
                'minutes_until' => $lc->minutes_until,
                'is_currently_live' => $lc->is_currently_live,
                'is_upcoming' => $lc->is_upcoming,
                'recording_url' => $lc->recording_url,
                'recording_video_url' => $lc->recording_video_url,
            ];

            // If live class ended and has a recording, use that as the video
            if ($lc->status === 'ended') {
                if ($lc->recording_video_path) {
                    $videoData['type'] = 'upload';
                    $videoData['url'] = $lc->recording_video_url;
                } elseif ($lc->recording_url) {
                    $videoData['type'] = 'url';
                    $videoData['url'] = $lc->recording_url;
                    // Check for YouTube/Vimeo
                    if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $lc->recording_url, $matches)) {
                        $videoData['is_youtube'] = true;
                        $videoData['youtube_id'] = $matches[2];
                        $videoData['embed_url'] = 'https://www.youtube.com/embed/' . $matches[2];
                    } elseif (preg_match('/vimeo\.com\/(\d+)/', $lc->recording_url, $matches)) {
                        $videoData['is_vimeo'] = true;
                        $videoData['vimeo_id'] = $matches[1];
                        $videoData['embed_url'] = 'https://player.vimeo.com/video/' . $matches[1];
                    }
                }
            }

            return $videoData;
        }

        if ($lesson->video_type === 'url' && $lesson->video_url) {
            $videoData['url'] = $lesson->video_url;
            
            if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $lesson->video_url, $matches)) {
                $videoData['is_youtube'] = true;
                $videoData['youtube_id'] = $matches[2];
                $videoData['embed_url'] = 'https://www.youtube.com/embed/' . $matches[2];
            }
            elseif (preg_match('/vimeo\.com\/(\d+)/', $lesson->video_url, $matches)) {
                $videoData['is_vimeo'] = true;
                $videoData['vimeo_id'] = $matches[1];
                $videoData['embed_url'] = 'https://player.vimeo.com/video/' . $matches[1];
            }
        } elseif ($lesson->video_type === 'upload' && $lesson->video_path) {
            $videoPath = ltrim($lesson->video_path, '/');
            $videoData['url'] = asset('storage/' . $videoPath);
        }
        
        return $videoData;
    }

    public function viewAssignmentSubmission($submissionId)
    {
        $user = $this->currentUser();
        
        $submission = AssignmentSubmission::with('files')
            ->where('user_id', $user->id)
            ->findOrFail($submissionId);

        $html = view('student.course-access.partials.assignment-submission', [
            'submission' => $submission,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function searchContent(Request $request, $courseId)
    {
        $user = $this->currentUser();
        $query = $request->input('q', '');

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);

        $results = [];

        foreach ($course->topics as $topic) {
            foreach ($topic->lessons as $lesson) {
                if (stripos($lesson->title, $query) !== false || stripos($lesson->description ?? '', $query) !== false) {
                    $results[] = [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'type' => 'lesson',
                        'topic' => $topic->title,
                    ];
                }
            }

            foreach ($topic->quizzes as $quiz) {
                if (stripos($quiz->title, $query) !== false || stripos($quiz->description ?? '', $query) !== false) {
                    $results[] = [
                        'id' => $quiz->id,
                        'title' => $quiz->title,
                        'type' => 'quiz',
                        'topic' => $topic->title,
                    ];
                }
            }

            foreach ($topic->assignments as $assignment) {
                if (stripos($assignment->title, $query) !== false || stripos($assignment->description ?? '', $query) !== false) {
                    $results[] = [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'type' => 'assignment',
                        'topic' => $topic->title,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Check if an item is accessible (all previous items are completed)
     */
    private function isItemAccessible($user, $enrollment, $courseId, $type, $itemId)
    {
        $course = Course::with(['topics.lessons', 'topics.quizzes', 'topics.assignments'])->findOrFail($courseId);
        
        $allItems = $this->getAllCourseItems($course);
        
        $currentIndex = $allItems->search(function($item) use ($type, $itemId) {
            return $item['type'] === $type && $item['id'] == $itemId;
        });
        
        if ($currentIndex === 0 || $currentIndex === false) {
            return $currentIndex !== false;
        }
        
        for ($i = 0; $i < $currentIndex; $i++) {
            $previousItem = $allItems[$i];
            
            if (!$this->isItemCompleted($user, $enrollment, $previousItem['type'], $previousItem['id'])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get all course items in sequential order
     */
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
        
        return $items->sortBy([
            ['topic_order', 'asc'],
            ['order', 'asc'],
        ])->values();
    }

    /**
     * Refresh sidebar with updated accessibility data
     */
    public function refreshSidebar($courseId)
    {
        $user = $this->currentUser();
        
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->firstOrFail();

        $course = Course::with([
            'topics.lessons',
            'topics.quizzes.questions',
            'topics.assignments.files',
            'instructor'
        ])->findOrFail($courseId);

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

        $accessibleItems = [];
        $allItems = $this->getAllCourseItems($course);
        foreach ($allItems as $index => $item) {
            $accessibleItems[$item['type'] . '_' . $item['id']] = $this->isItemAccessible($user, $enrollment, $courseId, $item['type'], $item['id']);
        }

        $topicsData = [];
        foreach ($course->topics as $index => $topic) {
            $progressStats = $topic->getProgressStats($userProgress, $userQuizAttempts, $userAssignmentSubmissions);
            
            $itemsData = [];
            
            foreach ($topic->lessons as $lesson) {
                $progress = $userProgress[$lesson->id] ?? null;
                $itemsData[] = [
                    'id' => $lesson->id,
                    'type' => 'lesson',
                    'title' => $lesson->title,
                    'duration_formatted' => $lesson->formatted_duration,
                    'is_completed' => $progress && $progress->is_completed,
                    'is_active' => false,
                    'is_accessible' => $accessibleItems['lesson_' . $lesson->id] ?? false,
                ];
            }
            
            foreach ($topic->quizzes as $quiz) {
                $attempt = $userQuizAttempts[$quiz->id] ?? null;
                $itemsData[] = [
                    'id' => $quiz->id,
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'duration_formatted' => '',
                    'is_completed' => $attempt && $attempt->passed,
                    'is_active' => false,
                    'is_accessible' => $accessibleItems['quiz_' . $quiz->id] ?? false,
                ];
            }
            
            foreach ($topic->assignments as $assignment) {
                $submission = $userAssignmentSubmissions[$assignment->id] ?? null;
                $itemsData[] = [
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'duration_formatted' => '',
                    'is_completed' => $submission !== null,
                    'is_active' => false,
                    'is_accessible' => $accessibleItems['assignment_' . $assignment->id] ?? false,
                ];
            }
            
            $topicsData[] = [
                'topic' => $topic,
                'index' => $index,
                'progress' => $progressStats,
                'items' => $itemsData,
            ];
        }

        $html = view('student.course-access.partials.sidebar', [
            'course' => $course,
            'topicsData' => $topicsData,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'accessibleItems' => $accessibleItems,
        ]);
    }

    /**
     * Check if an item is completed
     */
    /**
     * Check if course is fully completed
     */
    private function isCourseCompleted($user, $enrollment, $course)
    {
        $allItems = $this->getAllCourseItems($course);
        
        if ($allItems->isEmpty()) {
            return false;
        }
        
        foreach ($allItems as $item) {
            if (!$this->isItemCompleted($user, $enrollment, $item['type'], $item['id'])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check and update course completion status
     */
    private function checkAndUpdateCourseCompletion($user, $enrollment, $course)
    {
        if ($enrollment->status !== Enrollment::STATUS_COMPLETED && $this->isCourseCompleted($user, $enrollment, $course)) {
            $enrollment->update([
                'status' => Enrollment::STATUS_COMPLETED,
            ]);
            $enrollment->refresh();
            return true;
        }
        return false;
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

    /**
     * Show course completion page
     */
    public function showCompletionPage($courseId)
    {
        $user = $this->currentUser();
        
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->firstOrFail();

        $course = Course::with([
            'topics.lessons',
            'topics.quizzes',
            'topics.assignments',
            'instructor'
        ])->findOrFail($courseId);

        $totalLessons = $course->topics->flatMap->lessons->count();
        $totalQuizzes = $course->topics->flatMap->quizzes->count();
        $totalAssignments = $course->topics->flatMap->assignments->count();
        
        $completedLessons = $user->lessonProgress()
            ->where('enrollment_id', $enrollment->id)
            ->where('is_completed', true)
            ->count();
        
        $passedQuizzes = $user->quizAttempts()
            ->where('enrollment_id', $enrollment->id)
            ->where('passed', true)
            ->distinct()
            ->count('quiz_id');
        
        $submittedAssignments = $user->assignmentSubmissions()
            ->where('enrollment_id', $enrollment->id)
            ->distinct()
            ->count('assignment_id');

        return view('student.course-completion', compact(
            'course',
            'enrollment',
            'user',
            'totalLessons',
            'totalQuizzes',
            'totalAssignments',
            'completedLessons',
            'passedQuizzes',
            'submittedAssignments'
        ));
    }

    /**
     * Generate and download certificate as PDF
     */
    public function downloadCertificate($courseId)
    {
        $user = $this->currentUser();
        
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->firstOrFail();

        $course = Course::with('instructor')->findOrFail($courseId);

        $certificateData = [
            'student_name' => $user->name,
            'course_title' => $course->title,
            'instructor_name' => $course->instructor->name,
            'completion_date' => $enrollment->updated_at->format('F d, Y'),
            'certificate_number' => 'CERT-' . str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($user->id . $course->id), 0, 6)),
            'background_base64' => $this->certificateBackgroundBase64(),
        ];

        $pdf = Pdf::loadView('student.certificate', $certificateData)
            ->setPaper('a4', 'landscape')
            ->setOption('enable-local-file-access', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('enable_remote', true)
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        $filename = 'certificate-' . Str::slug($course->title) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function certificateBackgroundBase64(): ?string
    {
        $path = public_path('assets/front/img/certificate.png');

        if (! file_exists($path)) {
            return null;
        }

        return base64_encode(file_get_contents($path));
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

