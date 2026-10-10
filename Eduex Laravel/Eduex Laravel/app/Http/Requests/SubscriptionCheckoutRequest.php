<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => 'required|string|in:stripe,razorpay,paystack,flutterwave,paypal,sslcommerz,mollie,bkash,xpay,chapa,offline',
            'receipt_file' => 'nullable|required_if:payment_method,offline|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
