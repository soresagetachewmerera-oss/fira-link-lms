@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <!-- Page Header -->
                <div class="dashboard-header wow fadeInUp">
                    <div class="section-title-area text-left">
                        <div class="section-title">
                            <h6>{{ __('instructor.coupons_discounts') }}</h6>
                            <h2>{{ __('instructor.edit_coupon') }}</h2>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.1s" style="margin-top: 30px;">
                    <form class="settings-form" action="{{ route('instructor.coupons.update', $coupon->id) }}" method="POST" style="background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #f1f5f9;">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-4">
                            <label for="code" style="font-weight: 600; display: block; margin-bottom: 8px;">Coupon Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $coupon->code) }}" placeholder="e.g. MYCOURSE50" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <small class="form-hint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Unique code students enter at checkout.</small>
                            @error('code')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div style="display: flex; gap: 20px; flex-wrap: wrap;" class="mb-4">
                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="type" style="font-weight: 600; display: block; margin-bottom: 8px;">Discount Type <span class="text-danger">*</span></label>
                                <select id="type" name="type" class="form-control @error('type') is-invalid @enderror" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                    <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                </select>
                                @error('type')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="value" style="font-weight: 600; display: block; margin-bottom: 8px;">Discount Value <span class="text-danger">*</span></label>
                                <input type="number" id="value" name="value" step="0.01" min="0" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', $coupon->value) }}" placeholder="e.g. 10.00" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @error('value')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="course_id" style="font-weight: 600; display: block; margin-bottom: 8px;">Restrict to Course (Optional)</label>
                            <select id="course_id" name="course_id" class="form-control @error('course_id') is-invalid @enderror" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="">All your courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id', $coupon->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Select a course to restrict this coupon's usage. Leave empty to apply to all of your courses.</small>
                            @error('course_id')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div style="display: flex; gap: 20px; flex-wrap: wrap;" class="mb-4">
                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="status" style="font-weight: 600; display: block; margin-bottom: 8px;">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                    <option value="active" {{ old('status', $coupon->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $coupon->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="usage_limit" style="font-weight: 600; display: block; margin-bottom: 8px;">Usage Limit (Optional)</label>
                                <input type="number" id="usage_limit" name="usage_limit" min="1" class="form-control @error('usage_limit') is-invalid @enderror" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="Unlimited if empty" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @error('usage_limit')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; flex-wrap: wrap;" class="mb-5">
                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="valid_from" style="font-weight: 600; display: block; margin-bottom: 8px;">Valid From</label>
                                <input type="datetime-local" id="valid_from" name="valid_from" class="form-control @error('valid_from') is-invalid @enderror" value="{{ old('valid_from', $coupon->valid_from ? $coupon->valid_from->format('Y-m-d\TH:i') : '') }}" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @error('valid_from')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="valid_to" style="font-weight: 600; display: block; margin-bottom: 8px;">Valid To</label>
                                <input type="datetime-local" id="valid_to" name="valid_to" class="form-control @error('valid_to') is-invalid @enderror" value="{{ old('valid_to', $coupon->valid_to ? $coupon->valid_to->format('Y-m-d\TH:i') : '') }}" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @error('valid_to')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-actions" style="border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: right;">
                            <a href="{{ route('instructor.coupons.index') }}" class="theme-btn style-2" style="background-color: #cbd5e1; color: #334155; margin-right: 10px; padding: 10px 20px; border-radius: 4px;">Cancel</a>
                            <button type="submit" class="theme-btn style-2" style="padding: 10px 20px; border-radius: 4px;">Update Coupon</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
