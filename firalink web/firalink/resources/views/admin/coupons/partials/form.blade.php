<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method)
        @method($method)
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Coupon Details</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="code">Coupon Code <span class="text-danger">*</span></label>
                        <input type="text" id="code" name="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code', $coupon->code ?? '') }}" placeholder="e.g. SAVE20" required>
                        <small class="form-text text-muted">Unique identifier code that students enter at checkout.</small>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="type">Discount Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="percentage" {{ old('type', $coupon->type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                <option value="fixed" {{ old('type', $coupon->type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="value">Discount Value <span class="text-danger">*</span></label>
                            <input type="number" id="value" name="value" step="0.01" min="0"
                                class="form-control @error('value') is-invalid @enderror"
                                value="{{ old('value', $coupon->value ?? '') }}" placeholder="e.g. 20.00" required>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="course_id">Restrict to Course (Optional)</label>
                        <select class="form-control @error('course_id') is-invalid @enderror" id="course_id" name="course_id">
                            <option value="">Platform-wide (All courses)</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id', $coupon->course_id ?? '') == $course->id ? 'selected' : '' }}>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Leave empty to apply coupon to all courses on the platform.</small>
                        @error('course_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Status & Limit</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="active" {{ old('status', $coupon->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $coupon->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="usage_limit">Usage Limit (Optional)</label>
                        <input type="number" id="usage_limit" name="usage_limit" min="1"
                            class="form-control @error('usage_limit') is-invalid @enderror"
                            value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" placeholder="Unlimited if empty">
                        <small class="form-text text-muted">Max number of times this coupon can be redeemed.</small>
                        @error('usage_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Validity Period</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="valid_from">Start Date</label>
                        <input type="datetime-local" id="valid_from" name="valid_from"
                            class="form-control @error('valid_from') is-invalid @enderror"
                            value="{{ old('valid_from', isset($coupon->valid_from) ? $coupon->valid_from->format('Y-m-d\TH:i') : '') }}">
                        @error('valid_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="valid_to">End Date</label>
                        <input type="datetime-local" id="valid_to" name="valid_to"
                            class="form-control @error('valid_to') is-invalid @enderror"
                            value="{{ old('valid_to', isset($coupon->valid_to) ? $coupon->valid_to->format('Y-m-d\TH:i') : '') }}">
                        @error('valid_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-light mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</form>
