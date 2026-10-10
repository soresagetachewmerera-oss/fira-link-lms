<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Subscription Plan Service
 *
 * Handles management of subscription package plans for Admin.
 *
 * @package App\Services
 */
class SubscriptionPlanService
{
    /**
     * Get all subscription plans with optional filtering.
     */
    public function getAllPlans(array $filters = []): Collection
    {
        $query = SubscriptionPlan::with(['courses', 'bundles'])->withCount(['courses', 'bundles', 'userSubscriptions']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();
    }

    /**
     * Get all active subscription plans for front display.
     */
    public function getActivePlans(): Collection
    {
        return SubscriptionPlan::active()
            ->with(['courses', 'bundles'])
            ->withCount(['courses', 'bundles'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Find a plan by ID with relationships.
     */
    public function findPlan(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::with(['courses', 'bundles'])->findOrFail($id);
    }

    /**
     * Create a new subscription plan.
     */
    public function createPlan(array $data): SubscriptionPlan
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = Str::slug($data['name']);
            if (isset($data['features']) && is_string($data['features'])) {
                $data['features'] = array_values(array_filter(array_map('trim', explode("\n", $data['features']))));
            }

            $plan = SubscriptionPlan::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'discount_price' => $data['discount_price'] ?? null,
                'billing_period' => $data['billing_period'] ?? 'monthly',
                'duration_days' => $data['duration_days'] ?? 30,
                'course_limit' => $data['course_limit'] ?? null,
                'features' => $data['features'] ?? [],
                'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : false,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            // Sync included courses (standard and live)
            if (!empty($data['course_ids'])) {
                $plan->courses()->sync($data['course_ids']);
            }

            // Sync included bundles
            if (!empty($data['bundle_ids'])) {
                $plan->bundles()->sync($data['bundle_ids']);
            }

            return $plan;
        });
    }

    /**
     * Update an existing subscription plan.
     */
    public function updatePlan(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            if (isset($data['name']) && $data['name'] !== $plan->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            if (isset($data['features']) && is_string($data['features'])) {
                $data['features'] = array_values(array_filter(array_map('trim', explode("\n", $data['features']))));
            }

            $plan->update([
                'name' => $data['name'] ?? $plan->name,
                'slug' => $data['slug'] ?? $plan->slug,
                'description' => $data['description'] ?? $plan->description,
                'price' => $data['price'] ?? $plan->price,
                'discount_price' => array_key_exists('discount_price', $data) ? $data['discount_price'] : $plan->discount_price,
                'billing_period' => $data['billing_period'] ?? $plan->billing_period,
                'duration_days' => $data['duration_days'] ?? $plan->duration_days,
                'course_limit' => array_key_exists('course_limit', $data) ? $data['course_limit'] : $plan->course_limit,
                'features' => $data['features'] ?? $plan->features,
                'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : false,
                'status' => $data['status'] ?? $plan->status,
                'sort_order' => $data['sort_order'] ?? $plan->sort_order,
            ]);

            // Sync included courses
            $plan->courses()->sync($data['course_ids'] ?? []);

            // Sync included bundles
            $plan->bundles()->sync($data['bundle_ids'] ?? []);

            return $plan;
        });
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(SubscriptionPlan $plan): bool
    {
        $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
        return $plan->update(['status' => $newStatus]);
    }

    /**
     * Delete a subscription plan.
     */
    public function deletePlan(SubscriptionPlan $plan): bool
    {
        return DB::transaction(function () use ($plan) {
            $plan->courses()->detach();
            $plan->bundles()->detach();
            return $plan->delete();
        });
    }
}
