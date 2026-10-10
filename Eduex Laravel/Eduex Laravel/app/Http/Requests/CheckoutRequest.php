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

    public function rules(): array
    {
        $paymentMethod = $this->input('payment_method');
        $id = $this->route('id');
        $isBundle = $this->input('type') === 'bundle';
        
        $isFree = false;
        if ($isBundle) {
            $bundle = \App\Models\Bundle::find($id);
            if ($bundle && (float)$bundle->price == 0) {
                $isFree = true;
            }
        } else {
            $course = \App\Models\Course::find($id);
            if ($course && $course->pricing_model === 'free') {
                $isFree = true;
            }
        }
        
        $rules = [
            'payment_method' => [$isFree ? 'nullable' : 'required', Rule::in(['razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie', 'bkash', 'xpay', 'chapa'])],
        ];

        if ($paymentMethod === 'offline') {
            $rules['transaction_id'] = ['required', 'string', 'max:255'];
            $rules['receipt_file'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
            'transaction_id.required' => 'Transaction ID is required for offline payments.',
            'receipt_file.required' => 'Receipt file is required for offline payments.',
            'receipt_file.mimes' => 'Receipt must be a PDF, JPG, JPEG, or PNG file.',
            'receipt_file.max' => 'Receipt file size must not exceed 5MB.',
        ];
    }
}
