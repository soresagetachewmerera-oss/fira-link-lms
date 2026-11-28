<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Event Booking Request
 *
 * This request validates the data for event booking.
 *
 * @package App\Http\Requests\Event
 */
class StoreEventBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('category') || empty($this->input('category'))) {
            $this->merge(['category' => 'general']);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:200'],
            'category' => ['required', 'string', 'in:student,general'],
            'student_section' => ['nullable', 'string', 'required_if:category,student', 'in:A1,A2,A3,A4,A5,A6,A7,A8,A9,A10'],
            'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'ticket_type' => ['nullable', 'string', 'in:general,member'],
            // Slice 8: Chapa, or a bank transfer with the slip (a real file, checked on the server).
            'payment_method' => ['nullable', 'string', 'in:chapa,bank_transfer,free'],
            'slip' => ['nullable', 'file', 'max:10240'],
            'bank_reference' => ['nullable', 'string', 'max:120'],
            'contact_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Please select whether you are a Fira Link Student or a General Attendee.',
            'student_section.required_if' => 'Please select your Fira Link student section (A1 to A10).',
            'student_section.in' => 'Please select a valid student section from A1 to A10.',
        ];
    }
}
