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

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'seats' => ['required', 'integer', 'min:1'],
            'contact_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
