<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout Request
 *
 * This request validates the data for checkout.
 *
 * @package App\Http\Requests
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        return $user !== null && $user->isStudent();
    }

    protected function prepareForValidation(): void
    {
        $isBundle = $this->input('type') === 'bundle'
            || $this->is('*bundles*')
            || $this->routeIs('*bundle*');

        $merge = [];
        if ($isBundle) {
            $merge['type'] = 'bundle';
        }

        if ($this->has('payment_method') && is_string($this->payment_method)) {
            $merge['payment_method'] = strtolower(trim($this->payment_method));
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $isBundle = $this->input('type') === 'bundle'
            || $this->is('*bundles*')
            || $this->routeIs('*bundle*');
        
        $isFree = false;
        if ($isBundle) {
            $bundle = \App\Models\Bundle::find($id);
            if ($bundle && (float)$bundle->price <= 0) {
                $isFree = true;
            }
        } else {
            $course = \App\Models\Course::find($id);
            if ($course) {
                $coursePrice = $course->sale_price ?? $course->regular_price ?? $course->price ?? 0;
                if ($course->pricing_model === 'free' || (float)$coursePrice <= 0) {
                    $isFree = true;
                }
            }
        }

        $hasCoupon = $this->filled('coupon_code');
        
        // If course/bundle is naturally free or discounted by coupon to free, allow 'free'
        if ($isFree) {
            $paymentMethodRules = ['nullable', 'string', Rule::in(['chapa', 'offline', 'free'])];
        } elseif ($hasCoupon) {
            $paymentMethodRules = ['required', 'string', Rule::in(['chapa', 'offline', 'free'])];
        } else {
            $paymentMethodRules = ['required', 'string', Rule::in(['chapa', 'offline'])];
        }

        $rules = [
            'payment_method' => $paymentMethodRules,
        ];

        // Strict offline payment validation: prevent reuse of CBE / Telebirr transaction reference
        if ($this->input('payment_method') === 'offline') {
            $rules['transaction_id'] = [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('payments', 'transaction_id')->where(function ($query) {
                    return $query->whereIn('status', ['pending', 'completed']);
                }),
            ];
            $rules['receipt_file'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method. Allowed methods: Chapa or Offline bank transfer.',
            'transaction_id.required' => 'Transaction reference / ID is required for offline payments.',
            'transaction_id.unique' => 'This transaction reference has already been submitted. Please check your bank confirmation or contact support.',
            'receipt_file.mimes' => 'Receipt must be a PDF, JPG, JPEG, or PNG file.',
            'receipt_file.max' => 'Receipt file size must not exceed 5MB.',
        ];
    }
}
