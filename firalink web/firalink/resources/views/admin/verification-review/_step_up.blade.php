<div class="mt-2">
    <div class="text-muted small">{{ __('verification.admin.step_up', ['tier' => $tier]) }}</div>
    <div class="form-inline" style="gap: 8px;">
        <input name="step_up_code" class="form-control" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="{{ __('verification.admin.step_up_code') }}">
        <input name="current_password" type="password" class="form-control" autocomplete="current-password" placeholder="{{ __('verification.admin.step_up_password') }}">
    </div>
</div>
