@props(['action' => 'form_submit'])
@php
    $recaptchaEnabled = settings('recaptcha.config.enabled', false);
    $siteKey = settings('recaptcha.config.site_key');
    $version = settings('recaptcha.config.version', 'v2_checkbox');
@endphp

@if ($recaptchaEnabled && $siteKey)
    @if ($version === 'v3_invisible')
        <div {{ $attributes }}>
            <input type="hidden" name="g-recaptcha-response" class="recaptcha-token" data-action="{{ $action }}">
        </div>
        @error('g-recaptcha-response')
            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
        @enderror

        @once
            @push('scripts')
                <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}" async defer></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const siteKey = @json($siteKey);
                        const tokens = document.querySelectorAll('input.recaptcha-token');

                        if (!tokens.length) {
                            return;
                        }

                        tokens.forEach(function(input) {
                            const form = input.closest('form');

                            if (!form) {
                                return;
                            }

                            form.addEventListener('submit', function(event) {
                                if (form.dataset.recaptchaSubmitting === '1') {
                                    form.dataset.recaptchaSubmitting = '0';
                                    return;
                                }

                                event.preventDefault();

                                if (typeof grecaptcha === 'undefined') {
                                    form.submit();
                                    return;
                                }

                                grecaptcha.ready(function() {
                                    grecaptcha.execute(siteKey, {
                                        action: input.dataset.action || 'form_submit'
                                    }).then(function(token) {
                                        input.value = token;
                                        form.dataset.recaptchaSubmitting = '1';
                                        form.submit();
                                    }).catch(function() {
                                        form.submit();
                                    });
                                });
                            });
                        });
                    });
                </script>
            @endpush
        @endonce
    @else
        <div {{ $attributes->merge(['class' => 'g-recaptcha-container']) }}>
            <div class="g-recaptcha" data-sitekey="{{ $siteKey }}"></div>
            @error('g-recaptcha-response')
                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
            @enderror
        </div>

        @once
            @push('scripts')
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            @endpush
        @endonce
    @endif
@endif
