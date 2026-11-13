<?php

namespace App\Http\Requests\Instructor;

use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Update Assignment Submission Request
 *
 * This request validates the data for updating an assignment submission.
 *
 * @package App\Http\Requests\Instructor
 */
class UpdateAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isInstructor() || !$user->is_approved) {
            return false;
        }

        $submission = $this->route('submission');

        if ($submission instanceof AssignmentSubmission) {
            $submission->loadMissing('assignment.topic.course');

            return $submission->assignment
                && $submission->assignment->topic
                && $submission->assignment->topic->course
                && $submission->assignment->topic->course->instructor_id === $user->id;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                AssignmentSubmission::STATUS_PENDING,
                AssignmentSubmission::STATUS_GRADED,
                AssignmentSubmission::STATUS_RETURNED,
            ])],
            'grade' => [
                Rule::requiredIf(fn () => $this->input('status') === AssignmentSubmission::STATUS_GRADED),
                'nullable',
                'integer',
                'between:0,100',
            ],
            'instructor_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please choose a new status for the submission.',
            'status.in' => 'The selected status is invalid.',
            'grade.required' => 'Please provide a grade when marking the submission as graded.',
            'grade.integer' => 'The grade must be a whole number.',
            'grade.between' => 'The grade must be between 0 and 100.',
            'instructor_notes.max' => 'Feedback must be 5,000 characters or fewer.',
        ];
    }
}

