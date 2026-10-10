<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'billing_period' => 'required|in:monthly,quarterly,half_yearly,yearly,lifetime',
            'duration_days' => 'required|integer|min:1',
            'course_limit' => 'nullable|integer|min:0',
            'features' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'bundle_ids' => 'nullable|array',
            'bundle_ids.*' => 'exists:bundles,id',
        ];
    }
}
