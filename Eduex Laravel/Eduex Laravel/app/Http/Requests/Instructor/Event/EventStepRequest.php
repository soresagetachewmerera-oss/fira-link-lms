<?php

namespace App\Http\Requests\Instructor\Event;

use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Event Step Request
 *
 * This request validates the data for event step.
 *
 * @package App\Http\Requests\Instructor\Event
 */
class EventStepRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('instructor.events.store', 'instructor.events.basic.update')) {
            $this->merge([
                'title' => trim((string) $this->input('title')),
                'summary' => trim((string) $this->input('summary')),
                'location' => trim((string) $this->input('location')),
                'contact_phone' => trim((string) $this->input('contact_phone')),
                'contact_email' => trim((string) $this->input('contact_email')),
            ]);
        }

        if ($this->routeIs('instructor.events.speakers.update')) {
            $inputs = $this->input('speakers', []);
            $files = $this->file('speakers', []);

            $filteredInputs = [];
            $filteredFiles = [];

            foreach ($inputs as $index => $speaker) {
                $speaker = is_array($speaker) ? $speaker : [];
                $hasText = collect($speaker)
                    ->except(['existing_image'])
                    ->filter(fn ($value) => !blank($value))
                    ->isNotEmpty();
                $hasFile = data_get($files, "$index.image") !== null;
                $hasExisting = !blank($speaker['existing_image'] ?? null);

                if ($hasText || $hasFile || $hasExisting) {
                    $filteredInputs[] = $speaker;
                    $filteredFiles[] = $files[$index] ?? [];
                }
            }

            $this->merge(['speakers' => $filteredInputs]);
            $this->files->set('speakers', $filteredFiles);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isApprovedInstructor() ?? false;
    }

    protected function failedValidation(Validator $validator): void
    {
        ToastMagic::error($validator->errors()->first() ?? 'Please correct the highlighted errors.');

        throw new HttpResponseException(
            redirect()->back()->withErrors($validator)->withInput()
        );
    }

    public function rules(): array
    {
        if ($this->routeIs('instructor.events.store', 'instructor.events.basic.update')) {
            return $this->basicRules();
        }

        if ($this->routeIs('instructor.events.schedule.update')) {
            return $this->scheduleRules();
        }

        if ($this->routeIs('instructor.events.highlights.update')) {
            return $this->highlightRules();
        }

        if ($this->routeIs('instructor.events.speakers.update')) {
            return $this->speakerRules();
        }

        if ($this->routeIs('instructor.events.review.publish')) {
            return $this->publishRules();
        }

        return [];
    }

    private function basicRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location_description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'instructors' => ['nullable', 'array'],
            'instructors.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'instructor')
                    ->where('is_approved', true)
                ),
            ],
            'featured_image' => [
                $this->routeIs('instructor.events.store') ? 'required' : 'nullable',
                'image',
                'max:5120',
            ],
        ];
    }

    private function scheduleRules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'total_seats' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    private function highlightRules(): array
    {
        return [
            'highlights' => ['nullable', 'array'],
            'highlights.*.title' => ['nullable', 'string', 'max:255'],
            'highlights.*.description' => ['nullable', 'string', 'required_with:highlights.*.title'],
        ];
    }

    private function speakerRules(): array
    {
        return [
            'speakers' => ['nullable', 'array'],
            'speakers.*.name' => ['nullable', 'string', 'max:255', 'required_with:speakers.*.title,speakers.*.bio,speakers.*.email,speakers.*.phone,speakers.*.image', 'required_without:speakers.*.existing_image'],
            'speakers.*.title' => ['nullable', 'string', 'max:255'],
            'speakers.*.bio' => ['nullable', 'string'],
            'speakers.*.email' => ['nullable', 'email', 'max:255'],
            'speakers.*.phone' => ['nullable', 'string', 'max:50'],
            'speakers.*.existing_image' => ['nullable', 'string', 'max:255'],
            'speakers.*.image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    private function publishRules(): array
    {
        return [
            'is_published' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.max' => 'Summary must be 255 characters or fewer.',
        ];
    }
}
