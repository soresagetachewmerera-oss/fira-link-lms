<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Submit Quiz Request
 *
 * This request validates the data for submitting a quiz.
 *
 * @package App\Http\Requests
 */
class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isStudent());
    }

    public function rules(): array
    {
        return [
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.selected_answer' => ['required', 'integer', 'min:0'],
            'time_taken' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'quiz_id.required' => 'Quiz ID is required.',
            'quiz_id.exists' => 'The selected quiz does not exist.',
            'answers.required' => 'Quiz answers are required.',
            'answers.array' => 'Answers must be an array.',
            'answers.*.question_id.required' => 'Question ID is required for each answer.',
            'answers.*.question_id.exists' => 'One or more questions do not exist.',
            'answers.*.selected_answer.required' => 'Selected answer is required.',
            'answers.*.selected_answer.integer' => 'Selected answer must be a number.',
        ];
    }
}
