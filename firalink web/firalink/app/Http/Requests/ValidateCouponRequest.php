<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ValidateCouponRequest
 *
 * Validates request parameter for coupon code verification during checkout.
 *
 * @package App\Http\Requests
 */
class ValidateCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        return $user !== null && $user->isStudent();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'course_id' => ['required', 'exists:courses,id'],
        ];
    }
}
