<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLanguage;
use App\Models\CourseTopic;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Assignment;
use App\Models\AssignmentFile;
use App\Services\FileUploadService;
use App\Services\NotificationPreferenceService;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CourseUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Course Builder Controller
 *
 * This controller handles the course builder functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class CourseBuilderController extends Controller
{
    /**
     * The file upload service
     *
     * @var \App\Services\FileUploadService
     */
    protected $fileUploadService;

    /**
     * The constructor
     *
     * @param \App\Services\FileUploadService $fileUploadService
     * @param \App\Services\NotificationPreferenceService $notificationPreferenceService
     */

    public function __construct(FileUploadService $fileUploadService, private NotificationPreferenceService $notificationPreferenceService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display course builder page
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $user = Auth::user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        return view('instructor.course-builder', array_merge([
            'course' => null,
            'courseDataArray' => null,
        ], $this->builderFormData()));
    }

    /**
     * Store course
     *
     * @param \App\Http\Requests\StoreCourseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreCourseRequest $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending')->with('error', 'Your instructor account is not approved yet.');
        }

        $taxonomy = $this->resolveTaxonomySelections($request);
        $categoryModel = $taxonomy['category'];
        $categoryLabel = $taxonomy['categoryLabel'];
        $languageModel = $taxonomy['language'];
        $languageLabel = $taxonomy['languageLabel'];

        DB::beginTransaction();

        try {

            $featuredImagePath = null;
            if ($request->hasFile('featured_image')) {
                $featuredImagePath = $this->fileUploadService->uploadCourseImage($request->file('featured_image'));
            }

            $introVideoPath = null;
            if ($request->hasFile('intro_video')) {
                $introVideoPath = $this->fileUploadService->uploadIntroVideo($request->file('intro_video'));
            }

            $course = Course::create([
                'instructor_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'featured_image' => $featuredImagePath,
                'intro_video' => $introVideoPath,
                'visibility' => $request->visibility,
                'public_course' => filter_var($request->input('public_course', false), FILTER_VALIDATE_BOOLEAN),
                'max_students' => filter_var($request->input('public_course', false), FILTER_VALIDATE_BOOLEAN) ? null : $request->max_students,
                'difficulty' => $request->difficulty,
                'pricing_model' => $request->pricing_model,
                'regular_price' => $request->pricing_model === 'paid' ? $request->regular_price : null,
                'sale_price' => $request->pricing_model === 'paid' ? $request->sale_price : null,
                'schedule_enabled' => filter_var($request->input('schedule_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'schedule_date' => filter_var($request->input('schedule_enabled', false), FILTER_VALIDATE_BOOLEAN) ? $request->schedule_date : null,
                'course_category_id' => $categoryModel?->id,
                'course_language_id' => $languageModel?->id,
                'category' => $categoryLabel,
                'language' => $languageLabel,
                'tags' => $request->tags,
                'requirements' => $request->requirements,
                'objectives'   => $request->objectives,
                'status'       => $request->input('status') === 'published' ? 'pending' : 'draft',
                'is_live_course' => filter_var($request->input('is_live_course', false), FILTER_VALIDATE_BOOLEAN),
            ]);

            if ($request->has('topics') && is_array($request->topics)) {
                foreach ($request->topics as $topicIndex => $topicData) {
                    $topic = CourseTopic::create([
                        'course_id' => $course->id,
                        'title' => $topicData['title'] ?? '',
                        'description' => $topicData['description'] ?? null,
                        'order' => $topicIndex,
                    ]);

                    if (isset($topicData['lessons']) && is_array($topicData['lessons'])) {
                        foreach ($topicData['lessons'] as $lessonIndex => $lessonData) {
                            $lessonVideoPath = null;
                            $lessonVideoUrl = null;

                            $isLiveLesson = ($lessonData['video_type'] ?? 'url') === 'live';

                            if (!$isLiveLesson && ($lessonData['video_type'] ?? 'url') === 'upload') {
                                $videoFile = null;
                                $simpleKey = "lesson_video_{$topicIndex}_{$lessonIndex}";

                                if ($request->hasFile($simpleKey)) {
                                    $videoFile = $request->file($simpleKey);
                                } elseif ($request->hasFile("topics.{$topicIndex}.lessons.{$lessonIndex}.video_file")) {
                                    $videoFile = $request->file("topics.{$topicIndex}.lessons.{$lessonIndex}.video_file");
                                } else {
                                    $allFiles = $request->allFiles();
                                    if (isset($allFiles[$simpleKey])) {
                                        $videoFile = $allFiles[$simpleKey];
                                    } elseif (isset($allFiles['topics'][$topicIndex]['lessons'][$lessonIndex]['video_file'])) {
                                        $videoFile = $allFiles['topics'][$topicIndex]['lessons'][$lessonIndex]['video_file'];
                                    }
                                }

                                if ($videoFile && $videoFile instanceof \Illuminate\Http\UploadedFile) {
                                    $lessonVideoPath = $this->fileUploadService->uploadLessonVideo($videoFile);
                                } elseif (!empty($lessonData['video_path'])) {
                                    $lessonVideoPath = str_replace(asset('storage/'), '', $lessonData['video_path']);
                                }
                            } else {
                                $lessonVideoUrl = $lessonData['video_url'] ?? null;
                            }

                            Lesson::create([
                                'course_topic_id' => $topic->id,
                                'title'           => $lessonData['title'] ?? '',
                                'description'     => $lessonData['description'] ?? null,
                                'video_type'      => $lessonData['video_type'] ?? 'url',
                                'video_url'       => $isLiveLesson ? null : $lessonVideoUrl,
                                'video_path'      => $isLiveLesson ? null : $lessonVideoPath,
                                'duration'        => $lessonData['duration'] ?? null,
                                'is_preview'      => filter_var($lessonData['is_preview'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                'is_live'         => $isLiveLesson,
                                'order'           => $lessonIndex,
                            ]);
                        }
                    }

                    if (isset($topicData['quizzes']) && is_array($topicData['quizzes'])) {
                        foreach ($topicData['quizzes'] as $quizIndex => $quizData) {
                            $quiz = Quiz::create([
                                'course_topic_id' => $topic->id,
                                'title' => $quizData['title'] ?? '',
                                'description' => $quizData['description'] ?? null,
                                'passing_score' => $quizData['passing_score'] ?? 60,
                                'time_limit' => $quizData['time_limit'] ?? null,
                                'order' => $quizIndex,
                            ]);

                            if (isset($quizData['questions']) && is_array($quizData['questions'])) {
                                foreach ($quizData['questions'] as $questionIndex => $questionData) {
                                    $options = $questionData['options'] ?? [];
                                    if (is_string($options)) {
                                        $options = json_decode($options, true) ?? [];
                                    }

                                    QuizQuestion::create([
                                        'quiz_id' => $quiz->id,
                                        'question' => $questionData['question'] ?? '',
                                        'type' => $questionData['type'] ?? 'multiple-choice',
                                        'options' => $options,
                                        'correct_answer' => $questionData['correct_answer'] ?? 0,
                                        'order' => $questionIndex,
                                    ]);
                                }
                            }
                        }
                    }

                    if (isset($topicData['assignments']) && is_array($topicData['assignments'])) {
                        foreach ($topicData['assignments'] as $assignmentIndex => $assignmentData) {
                            $assignment = Assignment::create([
                                'course_topic_id' => $topic->id,
                                'title' => $assignmentData['title'] ?? '',
                                'description' => $assignmentData['description'] ?? null,
                                'instructions' => $assignmentData['instructions'] ?? null,
                                'order' => $assignmentIndex,
                            ]);

                            if (isset($assignmentData['existing_files']) && is_array($assignmentData['existing_files'])) {
                                foreach ($assignmentData['existing_files'] as $existingFile) {
                                    if (!empty($existingFile['url'])) {
                                        $url = $existingFile['url'];
                                        $filePath = str_replace(asset('storage/'), '', $url);
                                        $filePath = str_replace(asset(''), '', $filePath);
                                        $filePath = ltrim($filePath, '/');

                                        [$fileSize, $mimeType] = $this->publicDiskFileMeta($filePath);

                                        AssignmentFile::create([
                                            'assignment_id' => $assignment->id,
                                            'file_path' => $filePath,
                                            'original_filename' => $existingFile['name'] ?? basename($filePath),
                                            'file_size' => $fileSize,
                                            'mime_type' => $mimeType,
                                        ]);
                                    }
                                }
                            }

                            $assignmentFiles = [];
                            $allFiles = $request->allFiles();
                            $fileIndex = 0;

                            while (true) {
                                $simpleKey = "assignment_file_{$topicIndex}_{$assignmentIndex}_{$fileIndex}";
                                if (isset($allFiles[$simpleKey]) && $allFiles[$simpleKey] instanceof \Illuminate\Http\UploadedFile) {
                                    $assignmentFiles[] = $allFiles[$simpleKey];
                                    $fileIndex++;
                                } else {
                                    break;
                                }
                            }

                            if (empty($assignmentFiles)) {
                                $nestedKey = "topics.{$topicIndex}.assignments.{$assignmentIndex}.files";
                                if ($request->hasFile($nestedKey)) {
                                    $files = $request->file($nestedKey);
                                    if (!is_array($files)) {
                                        $files = [$files];
                                    }
                                    $assignmentFiles = array_merge($assignmentFiles, $files);
                                } elseif (isset($allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'])) {
                                    $files = $allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'];
                                    if (!is_array($files)) {
                                        $files = [$files];
                                    }
                                    $assignmentFiles = array_merge($assignmentFiles, $files);
                                }
                            }

                            foreach ($assignmentFiles as $file) {
                                if ($file instanceof \Illuminate\Http\UploadedFile) {
                                    $fileData = $this->fileUploadService->uploadAssignmentFile($file);

                                    AssignmentFile::create([
                                        'assignment_id' => $assignment->id,
                                        'file_path' => $fileData['file_path'],
                                        'original_filename' => $fileData['original_filename'],
                                        'file_size' => $fileData['file_size'],
                                        'mime_type' => $fileData['mime_type'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            $publish = filter_var($request->input('status', 'draft'), FILTER_VALIDATE_BOOLEAN) || $request->input('status') === 'published';
            $successMessage = $publish
                ? 'Course submitted successfully! It is pending admin approval.'
                : 'Course saved as draft successfully!';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'course' => [
                        'id' => $course->id,
                        'title' => $course->title,
                        'status' => $course->status,
                        'url' => route('instructor.courses'),
                    ],
                ], 200);
            }

            return redirect()->route('instructor.courses')
                ->with('success', $successMessage);

        } catch (ValidationException $e) {
            DB::rollBack();

            if (isset($featuredImagePath)) {
                $this->fileUploadService->deleteFile($featuredImagePath);
            }
            if (isset($introVideoPath)) {
                $this->fileUploadService->deleteFile($introVideoPath);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($featuredImagePath)) {
                $this->fileUploadService->deleteFile($featuredImagePath);
            }
            if (isset($introVideoPath)) {
                $this->fileUploadService->deleteFile($introVideoPath);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create course: ' . $e->getMessage(),
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again.',
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create course. Please try again.');
        }
    }

    /**
     * Display course edit page
     */
    public function edit($id)
    {
        $user = Auth::user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $course = Course::with([
            'topics.lessons',
            'topics.quizzes.questions',
            'topics.assignments.files'
        ])->findOrFail($id);

        if ($course->instructor_id != $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $courseDataArray = [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'visibility' => $course->visibility,
            'public_course' => $course->public_course,
            'max_students' => $course->max_students,
            'difficulty' => $course->difficulty,
            'pricing_model' => $course->pricing_model,
            'regular_price' => $course->regular_price,
            'sale_price' => $course->sale_price,
            'schedule_enabled' => $course->schedule_enabled,
            'schedule_date' => $course->schedule_date ? $course->schedule_date->format('Y-m-d\TH:i') : null,
            'course_category_id' => $course->course_category_id,
            'course_language_id' => $course->course_language_id,
            'category' => $course->category,
            'language' => $course->language,
            'category_label' => $course->category,
            'language_label' => $course->language,
            'tags' => $course->tags,
            'requirements' => $course->requirements,
            'objectives' => $course->objectives,
            'featured_image' => $course->featured_image ? asset('storage/' . $course->featured_image) : null,
            'intro_video' => $course->intro_video ? asset('storage/' . $course->intro_video) : null,
            'topics' => $course->topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'description' => $topic->description,
                    'lessons' => $topic->lessons->map(function ($lesson) {
                        return [
                            'id'          => $lesson->id,
                            'title'       => $lesson->title,
                            'description' => $lesson->description,
                            'type'        => 'lesson',
                            'videoType'   => $lesson->video_type,
                            'videoUrl'    => $lesson->video_url,
                            'videoPath'   => $lesson->video_path ? asset('storage/' . $lesson->video_path) : null,
                            'duration'    => $lesson->duration,
                            'isPreview'   => $lesson->is_preview,
                            'isLive'      => (bool) $lesson->is_live,
                            'liveClassId' => $lesson->live_class_id,
                        ];
                    })->toArray(),
                    'quizzes' => $topic->quizzes->map(function ($quiz) {
                        return [
                            'id' => $quiz->id,
                            'title' => $quiz->title,
                            'description' => $quiz->description,
                            'type' => 'quiz',
                            'passingScore' => $quiz->passing_score,
                            'timeLimit' => $quiz->time_limit,
                            'questions' => $quiz->questions->map(function ($question) {
                                return [
                                    'id' => $question->id,
                                    'question' => $question->question,
                                    'type' => $question->type,
                                    'options' => $question->options,
                                    'correctAnswer' => $question->correct_answer,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                    'assignments' => $topic->assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'title' => $assignment->title,
                            'description' => $assignment->description,
                            'instructions' => $assignment->instructions,
                            'type' => 'assignment',
                            'attachments' => $assignment->files->map(function ($file) {
                                return [
                                    'id' => $file->id,
                                    'url' => $file->url,
                                    'name' => $file->original_filename,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        return view('instructor.course-builder', array_merge([
            'course' => $course,
            'courseDataArray' => $courseDataArray,
        ], $this->builderFormData($course)));
    }

    /**
     * Update course
     */
    public function update(StoreCourseRequest $request, $id)
    {
        $user = Auth::user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending')->with('error', 'Your instructor account is not approved yet.');
        }

        $course = Course::findOrFail($id);

        if ($course->instructor_id != $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $taxonomy = $this->resolveTaxonomySelections($request);
        $categoryModel = $taxonomy['category'];
        $categoryLabel = $taxonomy['categoryLabel'];
        $languageModel = $taxonomy['language'];
        $languageLabel = $taxonomy['languageLabel'];

        $originalStatus = $course->status;

        DB::beginTransaction();

        try {
            $featuredImagePath = $course->featured_image;
            if ($request->hasFile('featured_image')) {
                if ($course->featured_image) {
                    $this->fileUploadService->deleteFile($course->featured_image);
                }
                $featuredImagePath = $this->fileUploadService->uploadCourseImage($request->file('featured_image'));
            }

            $introVideoPath = $course->intro_video;
            if ($request->hasFile('intro_video')) {
                if ($course->intro_video) {
                    $this->fileUploadService->deleteFile($course->intro_video);
                }
                $introVideoPath = $this->fileUploadService->uploadIntroVideo($request->file('intro_video'));
            }

            $newStatus = $request->input('status') === 'published' ? 'pending' : 'draft';

            if ($originalStatus === 'published' || $originalStatus === 'pending') {
                if ($newStatus !== 'draft') {
                    $newStatus = 'pending';
                }
            }

            $course->update([
                'title' => $request->title,
                'description' => $request->description,
                'featured_image' => $featuredImagePath,
                'intro_video' => $introVideoPath,
                'visibility' => $request->visibility,
                'public_course' => filter_var($request->input('public_course', false), FILTER_VALIDATE_BOOLEAN),
                'max_students' => filter_var($request->input('public_course', false), FILTER_VALIDATE_BOOLEAN) ? null : $request->max_students,
                'difficulty' => $request->difficulty,
                'pricing_model' => $request->pricing_model,
                'regular_price' => $request->pricing_model === 'paid' ? $request->regular_price : null,
                'sale_price' => $request->pricing_model === 'paid' ? $request->sale_price : null,
                'schedule_enabled' => filter_var($request->input('schedule_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'schedule_date' => filter_var($request->input('schedule_enabled', false), FILTER_VALIDATE_BOOLEAN) ? $request->schedule_date : null,
                'course_category_id' => $categoryModel?->id,
                'course_language_id' => $languageModel?->id,
                'category' => $categoryLabel,
                'language' => $languageLabel,
                'tags' => $request->tags,
                'requirements'   => $request->requirements,
                'objectives'     => $request->objectives,
                'status'         => $newStatus,
                'is_live_course' => filter_var($request->input('is_live_course', false), FILTER_VALIDATE_BOOLEAN),
            ]);

            $existingTopicIds = $course->topics->pluck('id')->toArray();
            $existingLessonIds = $course->topics->flatMap->lessons->pluck('id')->toArray();
            $existingQuizIds = $course->topics->flatMap->quizzes->pluck('id')->toArray();
            $existingAssignmentIds = $course->topics->flatMap->assignments->pluck('id')->toArray();
            $existingQuestionIds = $course->topics->flatMap->quizzes->flatMap->questions->pluck('id')->toArray();
            $existingAssignmentFileIds = $course->topics->flatMap->assignments->flatMap->files->pluck('id')->toArray();

            $requestTopicIds = [];
            $requestLessonIds = [];
            $requestQuizIds = [];
            $requestAssignmentIds = [];
            $requestQuestionIds = [];
            $requestAssignmentFileIds = [];

            if ($request->has('topics') && is_array($request->topics)) {
                foreach ($request->topics as $topicIndex => $topicData) {
                    $topicId = $topicData['id'] ?? null;

                    if ($topicId && CourseTopic::where('id', $topicId)->where('course_id', $course->id)->exists()) {
                        $topic = CourseTopic::find($topicId);
                        $topic->update([
                            'title' => $topicData['title'] ?? '',
                            'description' => $topicData['description'] ?? null,
                            'order' => $topicIndex,
                        ]);
                    } else {
                        $topic = CourseTopic::create([
                            'course_id' => $course->id,
                            'title' => $topicData['title'] ?? '',
                            'description' => $topicData['description'] ?? null,
                            'order' => $topicIndex,
                        ]);
                    }

                    $requestTopicIds[] = $topic->id;

                    if (isset($topicData['lessons']) && is_array($topicData['lessons'])) {
                        foreach ($topicData['lessons'] as $lessonIndex => $lessonData) {
                            $lessonId = $lessonData['id'] ?? null;
                            $lessonVideoPath = null;
                            $lessonVideoUrl = null;

                            $isLiveLesson   = ($lessonData['video_type'] ?? 'url') === 'live';

                            if (!$isLiveLesson && ($lessonData['video_type'] ?? 'url') === 'upload') {
                                $videoFile = null;
                                $simpleKey = "lesson_video_{$topicIndex}_{$lessonIndex}";

                                if ($request->hasFile($simpleKey)) {
                                    $videoFile = $request->file($simpleKey);
                                } elseif ($request->hasFile("topics.{$topicIndex}.lessons.{$lessonIndex}.video_file")) {
                                    $videoFile = $request->file("topics.{$topicIndex}.lessons.{$lessonIndex}.video_file");
                                } else {
                                    $allFiles = $request->allFiles();
                                    if (isset($allFiles[$simpleKey])) {
                                        $videoFile = $allFiles[$simpleKey];
                                    } elseif (isset($allFiles['topics'][$topicIndex]['lessons'][$lessonIndex]['video_file'])) {
                                        $videoFile = $allFiles['topics'][$topicIndex]['lessons'][$lessonIndex]['video_file'];
                                    }
                                }

                                if ($videoFile && $videoFile instanceof \Illuminate\Http\UploadedFile) {
                                    if ($lessonId && $lesson = Lesson::find($lessonId)) {
                                        if ($lesson->video_path) {
                                            $this->fileUploadService->deleteFile($lesson->video_path);
                                        }
                                    }
                                    $lessonVideoPath = $this->fileUploadService->uploadLessonVideo($videoFile);
                                } elseif (!empty($lessonData['video_path'])) {
                                    $lessonVideoPath = str_replace(asset('storage/'), '', $lessonData['video_path']);
                                } elseif ($lessonId && $lesson = Lesson::find($lessonId)) {
                                    $lessonVideoPath = $lesson->video_path;
                                }
                            } else {
                                $lessonVideoUrl = $lessonData['video_url'] ?? null;
                            }

                            if ($lessonId && $lesson = Lesson::find($lessonId)) {
                                if ($lesson->topic->course_id === $course->id) {
                                    $lesson->update([
                                        'course_topic_id' => $topic->id,
                                        'title'           => $lessonData['title'] ?? '',
                                        'description'     => $lessonData['description'] ?? null,
                                        'video_type'      => $lessonData['video_type'] ?? 'url',
                                        'video_url'       => $isLiveLesson ? null : $lessonVideoUrl,
                                        'video_path'      => $isLiveLesson ? null : $lessonVideoPath,
                                        'duration'        => $lessonData['duration'] ?? null,
                                        'is_preview'      => filter_var($lessonData['is_preview'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                        'is_live'         => $isLiveLesson,
                                        'order'           => $lessonIndex,
                                    ]);
                                } else {
                                    $lesson = Lesson::create([
                                        'course_topic_id' => $topic->id,
                                        'title'           => $lessonData['title'] ?? '',
                                        'description'     => $lessonData['description'] ?? null,
                                        'video_type'      => $lessonData['video_type'] ?? 'url',
                                        'video_url'       => $isLiveLesson ? null : $lessonVideoUrl,
                                        'video_path'      => $isLiveLesson ? null : $lessonVideoPath,
                                        'duration'        => $lessonData['duration'] ?? null,
                                        'is_preview'      => filter_var($lessonData['is_preview'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                        'is_live'         => $isLiveLesson,
                                        'order'           => $lessonIndex,
                                    ]);
                                }
                            } else {
                                $lesson = Lesson::create([
                                    'course_topic_id' => $topic->id,
                                    'title'           => $lessonData['title'] ?? '',
                                    'description'     => $lessonData['description'] ?? null,
                                    'video_type'      => $lessonData['video_type'] ?? 'url',
                                    'video_url'       => $isLiveLesson ? null : $lessonVideoUrl,
                                    'video_path'      => $isLiveLesson ? null : $lessonVideoPath,
                                    'duration'        => $lessonData['duration'] ?? null,
                                    'is_preview'      => filter_var($lessonData['is_preview'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                    'is_live'         => $isLiveLesson,
                                    'order'           => $lessonIndex,
                                ]);
                            }

                            $requestLessonIds[] = $lesson->id;
                        }
                    }

                    if (isset($topicData['quizzes']) && is_array($topicData['quizzes'])) {
                        foreach ($topicData['quizzes'] as $quizIndex => $quizData) {
                            $quizId = $quizData['id'] ?? null;

                            if ($quizId && $quiz = Quiz::find($quizId)) {
                                if ($quiz->topic->course_id === $course->id) {
                                    $quiz->update([
                                        'course_topic_id' => $topic->id,
                                        'title' => $quizData['title'] ?? '',
                                        'description' => $quizData['description'] ?? null,
                                        'passing_score' => $quizData['passing_score'] ?? 60,
                                        'time_limit' => $quizData['time_limit'] ?? null,
                                        'order' => $quizIndex,
                                    ]);

                                    $existingQuizQuestionIds = $quiz->questions->pluck('id')->toArray();
                                    $requestQuizQuestionIds = [];

                                    if (isset($quizData['questions']) && is_array($quizData['questions'])) {
                                        foreach ($quizData['questions'] as $questionIndex => $questionData) {
                                            $questionId = $questionData['id'] ?? null;

                                            $options = $questionData['options'] ?? [];
                                            if (is_string($options)) {
                                                $options = json_decode($options, true) ?? [];
                                            }

                                            if ($questionId && QuizQuestion::where('id', $questionId)->where('quiz_id', $quiz->id)->exists()) {
                                                $question = QuizQuestion::find($questionId);
                                                $question->update([
                                                    'quiz_id' => $quiz->id,
                                                    'question' => $questionData['question'] ?? '',
                                                    'type' => $questionData['type'] ?? 'multiple-choice',
                                                    'options' => $options,
                                                    'correct_answer' => $questionData['correct_answer'] ?? 0,
                                                    'order' => $questionIndex,
                                                ]);
                                            } else {
                                                $question = QuizQuestion::create([
                                                    'quiz_id' => $quiz->id,
                                                    'question' => $questionData['question'] ?? '',
                                                    'type' => $questionData['type'] ?? 'multiple-choice',
                                                    'options' => $options,
                                                    'correct_answer' => $questionData['correct_answer'] ?? 0,
                                                    'order' => $questionIndex,
                                                ]);
                                            }

                                            $requestQuizQuestionIds[] = $question->id;
                                            $requestQuestionIds[] = $question->id;
                                        }
                                    }

                                    $questionsToDelete = array_diff($existingQuizQuestionIds, $requestQuizQuestionIds);
                                    if (!empty($questionsToDelete)) {
                                        QuizQuestion::whereIn('id', $questionsToDelete)->delete();
                                    }
                                } else {
                                    $quiz = Quiz::create([
                                        'course_topic_id' => $topic->id,
                                        'title' => $quizData['title'] ?? '',
                                        'description' => $quizData['description'] ?? null,
                                        'passing_score' => $quizData['passing_score'] ?? 60,
                                        'time_limit' => $quizData['time_limit'] ?? null,
                                        'order' => $quizIndex,
                                    ]);

                                    if (isset($quizData['questions']) && is_array($quizData['questions'])) {
                                        foreach ($quizData['questions'] as $questionIndex => $questionData) {
                                            $options = $questionData['options'] ?? [];
                                            if (is_string($options)) {
                                                $options = json_decode($options, true) ?? [];
                                            }

                                            $question = QuizQuestion::create([
                                                'quiz_id' => $quiz->id,
                                                'question' => $questionData['question'] ?? '',
                                                'type' => $questionData['type'] ?? 'multiple-choice',
                                                'options' => $options,
                                                'correct_answer' => $questionData['correct_answer'] ?? 0,
                                                'order' => $questionIndex,
                                            ]);

                                            $requestQuestionIds[] = $question->id;
                                        }
                                    }
                                }
                            } else {
                                $quiz = Quiz::create([
                                    'course_topic_id' => $topic->id,
                                    'title' => $quizData['title'] ?? '',
                                    'description' => $quizData['description'] ?? null,
                                    'passing_score' => $quizData['passing_score'] ?? 60,
                                    'time_limit' => $quizData['time_limit'] ?? null,
                                    'order' => $quizIndex,
                                ]);

                                if (isset($quizData['questions']) && is_array($quizData['questions'])) {
                                    foreach ($quizData['questions'] as $questionIndex => $questionData) {
                                        $options = $questionData['options'] ?? [];
                                        if (is_string($options)) {
                                            $options = json_decode($options, true) ?? [];
                                        }

                                        $question = QuizQuestion::create([
                                            'quiz_id' => $quiz->id,
                                            'question' => $questionData['question'] ?? '',
                                            'type' => $questionData['type'] ?? 'multiple-choice',
                                            'options' => $options,
                                            'correct_answer' => $questionData['correct_answer'] ?? 0,
                                            'order' => $questionIndex,
                                        ]);

                                        $requestQuestionIds[] = $question->id;
                                    }
                                }
                            }

                            $requestQuizIds[] = $quiz->id;
                        }
                    }

                    if (isset($topicData['assignments']) && is_array($topicData['assignments'])) {
                        foreach ($topicData['assignments'] as $assignmentIndex => $assignmentData) {
                            $assignmentId = $assignmentData['id'] ?? null;

                            if ($assignmentId && $assignment = Assignment::find($assignmentId)) {
                                if ($assignment->topic->course_id === $course->id) {
                                    $assignment->update([
                                        'course_topic_id' => $topic->id,
                                        'title' => $assignmentData['title'] ?? '',
                                        'description' => $assignmentData['description'] ?? null,
                                        'instructions' => $assignmentData['instructions'] ?? null,
                                        'order' => $assignmentIndex,
                                    ]);

                                    $existingAssignmentFileIds = $assignment->files->pluck('id')->toArray();
                                    $requestAssignmentFileIdsForAssignment = [];

                                    if (isset($assignmentData['existing_files']) && is_array($assignmentData['existing_files'])) {
                                        foreach ($assignmentData['existing_files'] as $existingFile) {
                                            $fileId = $existingFile['id'] ?? null;

                                            if ($fileId && AssignmentFile::where('id', $fileId)->where('assignment_id', $assignment->id)->exists()) {
                                                $requestAssignmentFileIdsForAssignment[] = $fileId;
                                                $requestAssignmentFileIds[] = $fileId;
                                            } elseif (!empty($existingFile['url'])) {
                                                $url = $existingFile['url'];
                                                $filePath = str_replace(asset('storage/'), '', $url);
                                                $filePath = str_replace(asset(''), '', $filePath);
                                                $filePath = ltrim($filePath, '/');

                                                [$fileSize, $mimeType] = $this->publicDiskFileMeta($filePath);

                                                $newFile = AssignmentFile::create([
                                                    'assignment_id' => $assignment->id,
                                                    'file_path' => $filePath,
                                                    'original_filename' => $existingFile['name'] ?? basename($filePath),
                                                    'file_size' => $fileSize,
                                                    'mime_type' => $mimeType,
                                                ]);

                                                $requestAssignmentFileIdsForAssignment[] = $newFile->id;
                                                $requestAssignmentFileIds[] = $newFile->id;
                                            }
                                        }
                                    }

                                    $assignmentFiles = [];
                                    $allFiles = $request->allFiles();
                                    $fileIndex = 0;

                                    while (true) {
                                        $simpleKey = "assignment_file_{$topicIndex}_{$assignmentIndex}_{$fileIndex}";
                                        if (isset($allFiles[$simpleKey]) && $allFiles[$simpleKey] instanceof \Illuminate\Http\UploadedFile) {
                                            $assignmentFiles[] = $allFiles[$simpleKey];
                                            $fileIndex++;
                                        } else {
                                            break;
                                        }
                                    }

                                    if (empty($assignmentFiles)) {
                                        $nestedKey = "topics.{$topicIndex}.assignments.{$assignmentIndex}.files";
                                        if ($request->hasFile($nestedKey)) {
                                            $files = $request->file($nestedKey);
                                            if (!is_array($files)) {
                                                $files = [$files];
                                            }
                                            $assignmentFiles = array_merge($assignmentFiles, $files);
                                        } elseif (isset($allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'])) {
                                            $files = $allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'];
                                            if (!is_array($files)) {
                                                $files = [$files];
                                            }
                                            $assignmentFiles = array_merge($assignmentFiles, $files);
                                        }
                                    }

                                    foreach ($assignmentFiles as $file) {
                                        if ($file instanceof \Illuminate\Http\UploadedFile) {
                                            $fileData = $this->fileUploadService->uploadAssignmentFile($file);

                                            $newFile = AssignmentFile::create([
                                                'assignment_id' => $assignment->id,
                                                'file_path' => $fileData['file_path'],
                                                'original_filename' => $fileData['original_filename'],
                                                'file_size' => $fileData['file_size'],
                                                'mime_type' => $fileData['mime_type'],
                                            ]);

                                            $requestAssignmentFileIdsForAssignment[] = $newFile->id;
                                            $requestAssignmentFileIds[] = $newFile->id;
                                        }
                                    }

                                    $filesToDelete = array_diff($existingAssignmentFileIds, $requestAssignmentFileIdsForAssignment);
                                    if (!empty($filesToDelete)) {
                                        $filesToDeleteRecords = AssignmentFile::whereIn('id', $filesToDelete)->get();
                                        foreach ($filesToDeleteRecords as $fileToDelete) {
                                            $this->fileUploadService->deleteFile($fileToDelete->file_path);
                                        }
                                        AssignmentFile::whereIn('id', $filesToDelete)->delete();
                                    }
                                } else {
                                    $assignment = Assignment::create([
                                        'course_topic_id' => $topic->id,
                                        'title' => $assignmentData['title'] ?? '',
                                        'description' => $assignmentData['description'] ?? null,
                                        'instructions' => $assignmentData['instructions'] ?? null,
                                        'order' => $assignmentIndex,
                                    ]);

                                    if (isset($assignmentData['existing_files']) && is_array($assignmentData['existing_files'])) {
                                        foreach ($assignmentData['existing_files'] as $existingFile) {
                                            if (!empty($existingFile['url'])) {
                                                $url = $existingFile['url'];
                                                $filePath = str_replace(asset('storage/'), '', $url);
                                                $filePath = str_replace(asset(''), '', $filePath);
                                                $filePath = ltrim($filePath, '/');

                                                [$fileSize, $mimeType] = $this->publicDiskFileMeta($filePath);

                                                $newFile = AssignmentFile::create([
                                                    'assignment_id' => $assignment->id,
                                                    'file_path' => $filePath,
                                                    'original_filename' => $existingFile['name'] ?? basename($filePath),
                                                    'file_size' => $fileSize,
                                                    'mime_type' => $mimeType,
                                                ]);

                                                $requestAssignmentFileIds[] = $newFile->id;
                                            }
                                        }
                                    }

                                    $assignmentFiles = [];
                                    $allFiles = $request->allFiles();
                                    $fileIndex = 0;

                                    while (true) {
                                        $simpleKey = "assignment_file_{$topicIndex}_{$assignmentIndex}_{$fileIndex}";
                                        if (isset($allFiles[$simpleKey]) && $allFiles[$simpleKey] instanceof \Illuminate\Http\UploadedFile) {
                                            $assignmentFiles[] = $allFiles[$simpleKey];
                                            $fileIndex++;
                                        } else {
                                            break;
                                        }
                                    }

                                    if (empty($assignmentFiles)) {
                                        $nestedKey = "topics.{$topicIndex}.assignments.{$assignmentIndex}.files";
                                        if ($request->hasFile($nestedKey)) {
                                            $files = $request->file($nestedKey);
                                            if (!is_array($files)) {
                                                $files = [$files];
                                            }
                                            $assignmentFiles = array_merge($assignmentFiles, $files);
                                        } elseif (isset($allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'])) {
                                            $files = $allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'];
                                            if (!is_array($files)) {
                                                $files = [$files];
                                            }
                                            $assignmentFiles = array_merge($assignmentFiles, $files);
                                        }
                                    }

                                    foreach ($assignmentFiles as $file) {
                                        if ($file instanceof \Illuminate\Http\UploadedFile) {
                                            $fileData = $this->fileUploadService->uploadAssignmentFile($file);

                                            $newFile = AssignmentFile::create([
                                                'assignment_id' => $assignment->id,
                                                'file_path' => $fileData['file_path'],
                                                'original_filename' => $fileData['original_filename'],
                                                'file_size' => $fileData['file_size'],
                                                'mime_type' => $fileData['mime_type'],
                                            ]);

                                            $requestAssignmentFileIds[] = $newFile->id;
                                        }
                                    }
                                }
                            } else {
                                $assignment = Assignment::create([
                                    'course_topic_id' => $topic->id,
                                    'title' => $assignmentData['title'] ?? '',
                                    'description' => $assignmentData['description'] ?? null,
                                    'instructions' => $assignmentData['instructions'] ?? null,
                                    'order' => $assignmentIndex,
                                ]);


                                if (isset($assignmentData['existing_files']) && is_array($assignmentData['existing_files'])) {
                                    foreach ($assignmentData['existing_files'] as $existingFile) {
                                        if (!empty($existingFile['url'])) {
                                            $url = $existingFile['url'];
                                            $filePath = str_replace(asset('storage/'), '', $url);
                                            $filePath = str_replace(asset(''), '', $filePath);
                                            $filePath = ltrim($filePath, '/');

                                            [$fileSize, $mimeType] = $this->publicDiskFileMeta($filePath);

                                            $newFile = AssignmentFile::create([
                                                'assignment_id' => $assignment->id,
                                                'file_path' => $filePath,
                                                'original_filename' => $existingFile['name'] ?? basename($filePath),
                                                'file_size' => $fileSize,
                                                'mime_type' => $mimeType,
                                            ]);

                                            $requestAssignmentFileIds[] = $newFile->id;
                                        }
                                    }
                                }

                                $assignmentFiles = [];
                                $allFiles = $request->allFiles();
                                $fileIndex = 0;

                                while (true) {
                                    $simpleKey = "assignment_file_{$topicIndex}_{$assignmentIndex}_{$fileIndex}";
                                    if (isset($allFiles[$simpleKey]) && $allFiles[$simpleKey] instanceof \Illuminate\Http\UploadedFile) {
                                        $assignmentFiles[] = $allFiles[$simpleKey];
                                        $fileIndex++;
                                    } else {
                                        break;
                                    }
                                }

                                if (empty($assignmentFiles)) {
                                    $nestedKey = "topics.{$topicIndex}.assignments.{$assignmentIndex}.files";
                                    if ($request->hasFile($nestedKey)) {
                                        $files = $request->file($nestedKey);
                                        if (!is_array($files)) {
                                            $files = [$files];
                                        }
                                        $assignmentFiles = array_merge($assignmentFiles, $files);
                                    } elseif (isset($allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'])) {
                                        $files = $allFiles['topics'][$topicIndex]['assignments'][$assignmentIndex]['files'];
                                        if (!is_array($files)) {
                                            $files = [$files];
                                        }
                                        $assignmentFiles = array_merge($assignmentFiles, $files);
                                    }
                                }

                                foreach ($assignmentFiles as $file) {
                                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                                        $fileData = $this->fileUploadService->uploadAssignmentFile($file);

                                        $newFile = AssignmentFile::create([
                                            'assignment_id' => $assignment->id,
                                            'file_path' => $fileData['file_path'],
                                            'original_filename' => $fileData['original_filename'],
                                            'file_size' => $fileData['file_size'],
                                            'mime_type' => $fileData['mime_type'],
                                        ]);

                                        $requestAssignmentFileIds[] = $newFile->id;
                                    }
                                }
                            }

                            $requestAssignmentIds[] = $assignment->id;
                        }
                    }
                }
            }

            $lessonsToDelete = array_diff($existingLessonIds, $requestLessonIds);
            if (!empty($lessonsToDelete)) {
                $lessonsToDeleteRecords = Lesson::whereIn('id', $lessonsToDelete)
                    ->whereHas('topic', function ($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })->get();
                foreach ($lessonsToDeleteRecords as $lesson) {
                    if ($lesson->video_path) {
                        $this->fileUploadService->deleteFile($lesson->video_path);
                    }
                }
                Lesson::whereIn('id', $lessonsToDelete)
                    ->whereHas('topic', function ($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })->delete();
            }

            $quizzesToDelete = array_diff($existingQuizIds, $requestQuizIds);
            if (!empty($quizzesToDelete)) {
                Quiz::whereIn('id', $quizzesToDelete)
                    ->whereHas('topic', function ($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })->delete();
            }

            $assignmentsToDelete = array_diff($existingAssignmentIds, $requestAssignmentIds);
            if (!empty($assignmentsToDelete)) {
                $assignmentsToDeleteRecords = Assignment::whereIn('id', $assignmentsToDelete)
                    ->whereHas('topic', function ($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })->with('files')->get();
                foreach ($assignmentsToDeleteRecords as $assignment) {
                    foreach ($assignment->files as $file) {
                        $this->fileUploadService->deleteFile($file->file_path);
                    }
                }
                Assignment::whereIn('id', $assignmentsToDelete)
                    ->whereHas('topic', function ($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })->delete();
            }

            $topicsToDelete = array_diff($existingTopicIds, $requestTopicIds);
            if (!empty($topicsToDelete)) {
                CourseTopic::whereIn('id', $topicsToDelete)
                    ->where('course_id', $course->id)
                    ->delete();
            }

            DB::commit();

            $publish = filter_var($request->input('status', 'draft'), FILTER_VALIDATE_BOOLEAN) || $request->input('status') === 'published';
            $wasPublishedOrPending = ($originalStatus === 'published' || $originalStatus === 'pending');

            if ($newStatus !== 'draft') {
                $this->notifyCourseUpdate($course, $user);
            }

            $successMessage = $wasPublishedOrPending && $newStatus !== 'draft'
                ? 'Course updated successfully! Changes are pending admin approval.'
                : 'Course draft updated successfully!';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'course' => [
                        'id' => $course->id,
                        'title' => $course->title,
                        'status' => $course->status,
                        'url' => route('instructor.courses'),
                    ],
                ], 200);
            }

            return redirect()->route('instructor.courses')
                ->with('success', $successMessage);

        } catch (ValidationException $e) {
            DB::rollBack();

            if (isset($featuredImagePath) && $featuredImagePath !== $course->featured_image) {
                $this->fileUploadService->deleteFile($featuredImagePath);
            }
            if (isset($introVideoPath) && $introVideoPath !== $course->intro_video) {
                $this->fileUploadService->deleteFile($introVideoPath);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($featuredImagePath) && $featuredImagePath !== $course->featured_image) {
                $this->fileUploadService->deleteFile($featuredImagePath);
            }
            if (isset($introVideoPath) && $introVideoPath !== $course->intro_video) {
                $this->fileUploadService->deleteFile($introVideoPath);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update course: ' . $e->getMessage(),
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again.',
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update course. Please try again.');
        }
    }

    protected function notifyCourseUpdate(Course $course, User $instructor): void
    {
        $emailPreferences = settings('email.preferences', []);

        if (empty($emailPreferences['course_update_digest'])) {
            return;
        }

        if (!$this->notificationPreferenceService->prefers($instructor, 'course_updates')) {
            return;
        }

        $course->loadMissing('instructor');

        $enrollments = $course->enrollments()
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->with('user.notificationSettings')
            ->get()
            ->filter(function (Enrollment $enrollment) {
                $student = $enrollment->user;

                if (!$student) {
                    return false;
                }

                return $this->notificationPreferenceService->prefers($student, 'course_updates');
            })
            ->unique('user_id');

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->user;

            if (!$student || empty($student->email)) {
                continue;
            }

            try {
                Mail::to($student->email)->send(new CourseUpdatedMail(
                    $course,
                    $instructor,
                    sprintf('%s just refreshed the course content. Log in to explore the latest updates.', $instructor->name),
                    now(),
                    $student->name
                ));
            } catch (\Throwable $e) {
                \Log::error("Course updated notification email failed for student ID {$student->id}: " . $e->getMessage());
            }
        }
    }

    private function builderFormData(?Course $course = null): array
    {
        $categoryOptions = CourseCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $languageOptions = CourseLanguage::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return [
            'categoryOptions' => $categoryOptions,
            'languageOptions' => $languageOptions,
            'selectedCategoryId' => old('course_category_id', $course?->course_category_id),
            'selectedLanguageId' => old('course_language_id', $course?->course_language_id),
            'selectedCategoryLabel' => old('category_label', $course?->category),
            'selectedLanguageLabel' => old('language_label', $course?->language),
        ];
    }

    private function resolveTaxonomySelections(Request $request): array
    {
        $category = null;
        $language = null;

        if ($request->filled('course_category_id')) {
            $category = CourseCategory::find($request->input('course_category_id'));
        }

        if ($request->filled('course_language_id')) {
            $language = CourseLanguage::find($request->input('course_language_id'));
        }

        $categoryLabel = $category?->name ?? $request->input('category_label') ?? $request->input('category');
        $languageLabel = $language?->name ?? $request->input('language_label') ?? $request->input('language');

        $categoryLabel = $categoryLabel !== null ? trim($categoryLabel) : null;
        $languageLabel = $languageLabel !== null ? trim($languageLabel) : null;

        if ($categoryLabel === '') {
            $categoryLabel = null;
        }

        if ($languageLabel === '') {
            $languageLabel = null;
        }

        return [
            'category' => $category,
            'categoryLabel' => $categoryLabel,
            'language' => $language,
            'languageLabel' => $languageLabel,
        ];
    }

    private function publicDiskFileMeta(string $path): array
    {
        $size = 0;
        $mimeType = 'application/octet-stream';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $size = $disk->size($path);

            try {
                $mimeType = $disk->mimeType($path);
            } catch (\Throwable $e) {
                $mimeType = 'application/octet-stream';
            }
        }

        return [$size, $mimeType];
    }

    /**
     * Generate course description using AI
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\GeminiService $geminiService
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateDescription(Request $request, \App\Services\GeminiService $geminiService)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        try {
            $prompt = "Write a comprehensive and engaging course description for a course titled '{$request->title}'. 
                      The description should be structured with HTML tags suitable for a rich text editor.
                      
                      **Requirements:**
                      1. **Structure:**
                         - Start with a compelling **Introduction** (use <p>).
                         - Include a **What You Will Learn** section with a bulleted list (<ul><li>...</li></ul>).
                         - Include a **Target Audience** section (use <p>).
                         - End with a **Conclusion** or call to action (use <p>).
                      2. **Formatting:**
                         - Use <h2> for section headings.
                         - Use <strong> for emphasis.
                         - Do NOT use markdown code blocks (like ```html ... ```). Return raw HTML only.
                      3. **Tone:** Professional, encouraging, and informative.
                      4. **Length:** detailed enough to sell the course (approx. 300-500 words).";

            $description = $geminiService->generateContent($prompt);

            // Clean up any potential markdown code blocks if the AI still includes them
            $description = preg_replace('/^```html\s*|\s*```$/', '', trim($description));
            // Also remove just ``` if it appears
            $description = preg_replace('/^```\s*|\s*```$/', '', trim($description));

            return response()->json([
                'success' => true,
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate description: ' . $e->getMessage(),
            ], 500);
        }
    }
}
