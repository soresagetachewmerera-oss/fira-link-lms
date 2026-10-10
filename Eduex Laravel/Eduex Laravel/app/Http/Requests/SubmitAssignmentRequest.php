<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Submit Assignment Request
 *
 * This request validates the data for submitting an assignment.
 *
 * @package App\Http\Requests
 */
class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isStudent());
    }

    public function rules(): array
    {
        return [
            'assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'submission_text' => ['nullable', 'string'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240'], 
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_id.required' => 'Assignment ID is required.',
            'assignment_id.exists' => 'The selected assignment does not exist.',
            'files.array' => 'Files must be an array.',
            'files.max' => 'You can upload a maximum of 10 files.',
            'files.*.file' => 'Each file must be a valid file.',
            'files.*.max' => 'Each file must not exceed 10MB.',
        ];
    }
}
