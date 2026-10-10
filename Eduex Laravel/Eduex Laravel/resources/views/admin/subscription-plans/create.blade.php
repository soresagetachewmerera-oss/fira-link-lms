@extends('layouts.admin')

@section('title', 'Create Package Plan')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.subscription-plans.index') }}">Package Plans</a></div>
        <div class="breadcrumb-item active">Create Package Plan</div>
    </div>
@endsection

@section('main-content')
    <form action="{{ route('admin.subscription-plans.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Package Plan Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="name">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Pro Monthly Plan" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Brief summary of plan benefits">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="price">Regular Price ({{ currency_symbol() }}) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" placeholder="49.99" required>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="discount_price">Offer / Discounted Price ({{ currency_symbol() }}) <small class="text-muted">(Optional)</small></label>
                                    <input type="number" step="0.01" name="discount_price" id="discount_price" class="form-control @error('discount_price') is-invalid @enderror" value="{{ old('discount_price') }}" placeholder="29.99">
                                    <small class="form-text text-muted">Leave empty if no special offer discount.</small>
                                    @error('discount_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="billing_period">Billing Cycle <span class="text-danger">*</span></label>
                                    <select name="billing_period" id="billing_period" class="form-control">
                                        <option value="monthly" {{ old('billing_period') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('billing_period') == 'quarterly' ? 'selected' : '' }}>Quarterly (3 Months)</option>
                                        <option value="half_yearly" {{ old('billing_period') == 'half_yearly' ? 'selected' : '' }}>Half Yearly (6 Months)</option>
                                        <option value="yearly" {{ old('billing_period') == 'yearly' ? 'selected' : '' }}>Yearly (12 Months)</option>
                                        <option value="lifetime" {{ old('billing_period') == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="duration_days">Duration (Days) <span class="text-danger">*</span></label>
                                    <input type="number" name="duration_days" id="duration_days" class="form-control @error('duration_days') is-invalid @enderror" value="{{ old('duration_days', 30) }}" required>
                                    <small class="form-text text-muted">e.g. 30 for monthly, 365 for yearly.</small>
                                    @error('duration_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="features">Features & Benefits List <small class="text-muted">(One feature per line)</small></label>
                            <textarea name="features" id="features" class="form-control" rows="5" placeholder="Access to 10 Premium Courses&#10;Live Class Access Included&#10;Certificate of Completion&#10;24/7 Priority Support">{{ old('features') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Included Courses Selection Card -->
                <div class="card">
                    <div class="card-header justify-content-between align-items-center">
                        <h4>Select Accessible Courses & Live Classes</h4>
                        <small class="text-muted">Students with this active subscription will gain access to selected courses.</small>
                    </div>
                    <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                        <div class="row">
                            @forelse($courses as $course)
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" class="custom-control-input" id="course_{{ $course->id }}" {{ is_array(old('course_ids')) && in_array($course->id, old('course_ids')) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="course_{{ $course->id }}">
                                            {{ $course->title }}
                                            @if(($course->live_classes_count ?? 0) > 0)
                                                <span class="badge badge-danger ml-1">Live</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted">No courses available.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Included Bundles Selection Card -->
                <div class="card">
                    <div class="card-header justify-content-between align-items-center">
                        <h4>Select Accessible Course Bundles</h4>
                        <small class="text-muted">Selected course bundles will also be included in this package plan.</small>
                    </div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <div class="row">
                            @forelse($bundles as $bundle)
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="bundle_ids[]" value="{{ $bundle->id }}" class="custom-control-input" id="bundle_{{ $bundle->id }}" {{ is_array(old('bundle_ids')) && in_array($bundle->id, old('bundle_ids')) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="bundle_{{ $bundle->id }}">{{ $bundle->title }}</label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted">No course bundles available.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Settings</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="sort_order">Display Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                        </div>

                        <div class="form-group mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="is_featured" value="1" class="custom-control-input" id="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="is_featured">Highlight as Featured Plan</label>
                            </div>
                            <small class="form-text text-muted">Adds a "Recommended" badge on the pricing page.</small>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-save"></i> Save Package Plan
                        </button>
                        <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary btn-block mt-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
