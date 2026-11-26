<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BusinessCategory;
use App\Models\Organization;
use App\Support\BusinessRole;
use App\Support\Capability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Release 2a: admins manage business categories (labels in both languages, allowed activities, order,
 * on/off, "legally confirmed"). The code never changes once created; the role can change only while no
 * business uses the category. Removing an activity also removes it from that category's businesses.
 */
class BusinessCategoryAdminController extends Controller
{
    public function index()
    {
        return view('admin.trade.categories.index', [
            'categories' => BusinessCategory::withCount(['organizations'])->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.trade.categories.form', ['category' => new BusinessCategory(['capabilities' => [], 'is_active' => true]), 'inUse' => false]);
    }

    public function edit(BusinessCategory $category)
    {
        return view('admin.trade.categories.form', ['category' => $category, 'inUse' => $category->organizations()->exists()]);
    }

    private function rules(?BusinessCategory $category): array
    {
        return [
            'code' => $category ? 'prohibited' : ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:business_categories,code'],
            'label_en' => 'required|string|max:120',
            'label_am' => 'required|string|max:120',
            'description_en' => 'nullable|string|max:500',
            'description_am' => 'nullable|string|max:500',
            'business_role' => ['required', Rule::in(BusinessRole::values())],
            'capabilities' => 'required|array|min:1',
            'capabilities.*' => ['string', Rule::in(Capability::values())],
            'sort_order' => 'nullable|integer|min:0|max:65000',
            'is_active' => 'nullable|boolean',
            'legally_confirmed' => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(null));
        $category = new BusinessCategory($this->fields($request, $data));
        $category->code = $data['code'];
        $category->save();
        $this->audit($request, $category, 'business_category_created', [], $category->toOption());

        return redirect()->route('admin.trade.categories.index')->with('success', __('categories.saved'));
    }

    public function update(Request $request, BusinessCategory $category)
    {
        $data = $request->validate($this->rules($category));
        if ($data['business_role'] !== $category->business_role && $category->organizations()->exists()) {
            return back()->withInput()->with('error', __('categories.role_locked'));
        }

        $before = $category->toOption();
        DB::transaction(function () use ($request, $category, $data) {
            $category->fill($this->fields($request, $data))->save();
            // Businesses keep only activities the category still allows.
            DB::table('organization_capabilities')
                ->whereIn('organization_id', $category->organizations()->pluck('id'))
                ->whereNotIn('capability', $category->allowedCapabilities())
                ->delete();
        });
        $this->audit($request, $category, 'business_category_updated', $before, $category->fresh()->toOption());

        return redirect()->route('admin.trade.categories.index')->with('success', __('categories.saved'));
    }

    private function fields(Request $request, array $data): array
    {
        return [
            'label_en' => $data['label_en'],
            'label_am' => $data['label_am'],
            'description_en' => $data['description_en'] ?? null,
            'description_am' => $data['description_am'] ?? null,
            'business_role' => $data['business_role'],
            'capabilities' => array_values(array_intersect(Capability::values(), $data['capabilities'])),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'legally_confirmed' => $request->boolean('legally_confirmed'),
        ];
    }

    private function audit(Request $request, BusinessCategory $category, string $action, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => BusinessCategory::class,
            'auditable_id' => $category->id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
