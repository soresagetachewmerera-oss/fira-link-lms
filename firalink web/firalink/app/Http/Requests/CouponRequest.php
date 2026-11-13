<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Coupon Request
 *
 * Validates data for creating and updating coupons.
 *
 * @package App\Http\Requests
 */
class CouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        return $user !== null && ($user->isAdmin() || $user->isInstructor());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon'); 
        if (is_object($couponId)) {
            $couponId = $couponId->id;
        }

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
