<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreLiveClassRequest
 *
 * Validates data for creating or updating a live class.
 *
 * @package App\Http\Requests
 */
class StoreLiveClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        return $user !== null && $user->isApprovedInstructor();
    }

    public function rules(): array
    {
        return [
            'course_id'        => ['required', 'integer', 'exists:courses,id'],
            'lesson_id'        => ['nullable', 'integer', 'exists:lessons,id'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'join_url'         => ['required', 'url', 'max:2048'],
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required'    => 'Please select a course.',
            'course_id.exists'      => 'The selected course does not exist.',
            'title.required'        => 'Live class title is required.',
            'join_url.required'     => 'A join URL (Zoom/Meet/etc.) is required.',
            'join_url.url'          => 'Please enter a valid URL.',
            'scheduled_at.required' => 'Please select the scheduled date and time.',
            'scheduled_at.after'    => 'Scheduled time must be in the future.',
        ];
    }
}
