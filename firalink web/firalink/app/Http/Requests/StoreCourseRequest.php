<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Course Request
 *
 * This request validates the data for storing a course.
 *
 * @package App\Http\Requests
 */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        return $user !== null && $user->isApprovedInstructor();
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'visibility' => ['required', Rule::in(['public', 'private', 'draft'])],
            
            'public_course' => ['nullable', 'in:0,1,true,false,on,off'],
            'max_students' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
            
            'pricing_model' => ['required', Rule::in(['free', 'paid'])],
            'regular_price' => ['nullable', 'required_if:pricing_model,paid', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:regular_price'],
            
            'schedule_enabled' => ['nullable', 'in:0,1,true,false,on,off'],
            'schedule_date' => ['nullable', 'required_if:schedule_enabled,1', 'required_if:schedule_enabled,true', 'required_if:schedule_enabled,on', 'date'],
            
            'category' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:255'],
            'course_category_id' => ['nullable', 'exists:course_categories,id'],
            'course_language_id' => ['nullable', 'exists:course_languages,id'],
            'category_label' => ['nullable', 'string', 'max:255'],
            'language_label' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'is_live_course' => ['nullable', 'in:0,1,true,false,on,off'],
            
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // 5MB
            'intro_video' => ['nullable', 'file', 'mimes:mp4,avi,mov,webm', 'max:512000'], // 500MB
            
            'topics' => ['nullable', 'array'],
            'topics.*.id' => ['nullable', 'integer'],
            'topics.*.title' => ['required_with:topics', 'string', 'max:255'],
            'topics.*.description' => ['nullable', 'string'],
            'topics.*.order' => ['nullable', 'integer'],
            
            'topics.*.lessons' => ['nullable', 'array'],
            'topics.*.lessons.*.title' => ['required_with:topics.*.lessons', 'string', 'max:255'],
            'topics.*.lessons.*.description' => ['nullable', 'string'],
            'topics.*.lessons.*.video_type' => ['required_with:topics.*.lessons', Rule::in(['url', 'upload', 'live'])],
            'topics.*.lessons.*.video_url' => ['nullable', 'url'],
            'topics.*.lessons.*.video_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,webm', 'max:512000'],
            'topics.*.lessons.*.video_path' => ['nullable', 'string'],
            'topics.*.lessons.*.id' => ['nullable', 'integer'],
            'topics.*.lessons.*.duration' => ['nullable', 'integer', 'min:0'],
            'topics.*.lessons.*.is_preview' => ['nullable', 'in:0,1,true,false,on,off'],
            'topics.*.lessons.*.order' => ['nullable', 'integer'],
            
            'topics.*.quizzes' => ['nullable', 'array'],
            'topics.*.quizzes.*.id' => ['nullable', 'integer'],
            'topics.*.quizzes.*.title' => ['required_with:topics.*.quizzes', 'string', 'max:255'],
            'topics.*.quizzes.*.description' => ['nullable', 'string'],
            'topics.*.quizzes.*.passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'topics.*.quizzes.*.time_limit' => ['nullable', 'integer', 'min:0'],
            'topics.*.quizzes.*.order' => ['nullable', 'integer'],
            
            'topics.*.quizzes.*.questions' => ['nullable', 'array'],
            'topics.*.quizzes.*.questions.*.id' => ['nullable', 'integer'],
            'topics.*.quizzes.*.questions.*.question' => ['required_with:topics.*.quizzes.*.questions', 'string'],
            'topics.*.quizzes.*.questions.*.type' => ['required_with:topics.*.quizzes.*.questions', Rule::in(['multiple-choice', 'true-false'])],
            'topics.*.quizzes.*.questions.*.options' => ['required_with:topics.*.quizzes.*.questions', 'array', 'min:2'],
            'topics.*.quizzes.*.questions.*.options.*' => ['required', 'string'],
            'topics.*.quizzes.*.questions.*.correct_answer' => ['required_with:topics.*.quizzes.*.questions', 'integer', 'min:0'],
            'topics.*.quizzes.*.questions.*.order' => ['nullable', 'integer'],
            
            'topics.*.assignments' => ['nullable', 'array'],
            'topics.*.assignments.*.id' => ['nullable', 'integer'],
            'topics.*.assignments.*.title' => ['required_with:topics.*.assignments', 'string', 'max:255'],
            'topics.*.assignments.*.description' => ['nullable', 'string'],
            'topics.*.assignments.*.instructions' => ['nullable', 'string'],
            'topics.*.assignments.*.order' => ['nullable', 'integer'],
            
            'topics.*.assignments.*.files' => ['nullable', 'array'],
            'topics.*.assignments.*.files.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,zip,txt', 'max:10240'], // 10MB
        ];
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required.',
            'pricing_model.required' => 'Please select a pricing model.',
            'regular_price.required_if' => 'Regular price is required for paid courses.',
            'sale_price.lt' => 'Sale price must be less than regular price.',
            'topics.*.title.required_with' => 'Topic title is required.',
            'topics.*.lessons.*.title.required_with' => 'Lesson title is required.',
            'topics.*.lessons.*.video_type.required_with' => 'Please select a video type.',
            'topics.*.lessons.*.video_url.required_if' => 'Video URL is required when video type is URL.',
            'topics.*.lessons.*.video_file.required_if' => 'Video file is required when video type is upload.',
            'topics.*.quizzes.*.title.required_with' => 'Quiz title is required.',
            'topics.*.quizzes.*.questions.*.question.required_with' => 'Question text is required.',
            'topics.*.quizzes.*.questions.*.options.required_with' => 'Question options are required.',
            'topics.*.quizzes.*.questions.*.correct_answer.required_with' => 'Correct answer is required.',
            'topics.*.assignments.*.title.required_with' => 'Assignment title is required.',
        ];
    }
}
