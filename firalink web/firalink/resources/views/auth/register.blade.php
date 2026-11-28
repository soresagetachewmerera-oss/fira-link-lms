@extends('layouts.app')

@section('content')
    <!-- Register Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 pt-6 pb-6" style="background: #F9F7FA; min-height: 85vh; display: flex; align-items: center;">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-7 col-md-9">
                    <div class="mhq-register-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-register-header text-center mb-4">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="register-logo mb-3">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}" alt="{{ $siteName }} logo">
                            </a>
                            <h1 class="wow fadeInUp" data-wow-delay=".2s" style="font-size: 1.75rem; font-weight: 700; color: #1E1B4B;">{{ __('auth.create_your_account') }}</h1>
                            <p class="wow fadeInUp" data-wow-delay=".3s">{{ __('auth.join_learners') }}</p>
                        </div>

                        <form action="{{ route('register') }}" method="POST" enctype="multipart/form-data" class="mhq-register-form">
                            @csrf


                            <div class="row">
                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".4s">
                                    <div class="form-clt">
                                        <label class="visually-hidden" for="first_name">{{ __('auth.first_name') }}</label>
                                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}"
                                            placeholder="{{ __('auth.first_name') }}" class="@error('first_name') is-invalid @enderror"
                                            required autocomplete="given-name" autofocus>
                                    </div>
                                    @error('first_name')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".4s">
                                    <div class="form-clt">
                                        <label class="visually-hidden" for="last_name">{{ __('auth.last_name') }}</label>
                                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}"
                                            placeholder="{{ __('auth.last_name') }}" class="@error('last_name') is-invalid @enderror"
                                            required autocomplete="family-name">
                                    </div>
                                    @error('last_name')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay=".5s">
                                    <div class="form-clt">
                                        <label class="visually-hidden" for="email">{{ __('auth.your_email') }}</label>
                                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                                            placeholder="{{ __('auth.your_email') }}" class="@error('email') is-invalid @enderror" required
                                            autocomplete="email">
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay=".55s">
                                    <div class="form-clt">
                                        <label class="visually-hidden" for="phone">Phone number</label>
                                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                            placeholder="Ethiopian Phone Number (e.g. 0911234567 or +251911234567)" class="@error('phone') is-invalid @enderror" required
                                            autocomplete="tel">
                                    </div>
                                    @error('phone')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1"><i class="fas fa-shield-alt text-primary"></i> A verification code will be sent to this phone via SMS.</small>
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay=".6s">
                                    <div class="form-clt position-relative">
                                        <label class="visually-hidden" for="password">{{ __('auth.your_password') }}</label>
                                        <input type="password" name="password" id="password" placeholder="{{ __('auth.your_password') }}"
                                            class="@error('password') is-invalid @enderror" required
                                            autocomplete="new-password">
                                        <button type="button" class="toggle-password position-absolute" aria-label="Show password" aria-pressed="false">
                                            <i class="fa-regular fa-eye" id="togglePassword"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-2">{{ __('auth.password_chars_requirement') }}</small>
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay=".7s">
                                    <div class="form-clt position-relative">
                                        <label class="visually-hidden" for="password_confirmation">{{ __('auth.confirm_password') }}</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            placeholder="{{ __('auth.confirm_password') }}" required autocomplete="new-password">
                                        <button type="button" class="toggle-password-confirm position-absolute" aria-label="Show password confirmation" aria-pressed="false">
                                            <i class="fa-regular fa-eye" id="togglePasswordConfirm"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay=".75s">
                                    <div class="p-3 rounded-3 mb-2" style="background: #FDF4FF; border: 1.5px dashed #D8B4FE;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-preview-box rounded-circle position-relative flex-shrink-0" style="width: 56px; height: 56px; background: #EDE9FE; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #8B5CF6;">
                                                <img id="registerPhotoPreview" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                                <i id="registerPhotoPlaceholder" class="fa-solid fa-camera fs-5" style="color: #6D1A95;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label for="profile_photo" class="fw-bold d-block mb-1 text-dark" style="font-size: 13.5px; cursor: pointer;">
                                                    Profile Photo <span class="badge" style="background: #F3E8FF; color: #6D1A95; font-size: 10.5px; font-weight: 600;">Recommended</span>
                                                </label>
                                                <div class="text-muted" style="font-size: 11.5px;">Upload a real photo so fellow exporters and event attendees recognize you.</div>
                                                <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control form-control-sm mt-2 @error('profile_photo') is-invalid @enderror" style="font-size: 12px;">
                                            </div>
                                        </div>
                                        @error('profile_photo')
                                            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mb-4 wow fadeInUp" data-wow-delay=".8s">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    {{ __('auth.i_agree_to') }} <a href="{{ route('terms') }}" class="text-primary">{{ __('auth.terms_of_service') }}</a> {{ __('auth.and') }} <a href="{{ route('privacy') }}"
                                        class="text-primary">{{ __('auth.privacy_policy') }}</a>
                                </label>
                            </div>

                            <x-recaptcha-widget class="mb-4 wow fadeInUp" data-wow-delay=".85s" action="student_register" />

                            <button type="submit" class="theme-btn w-100 mb-3 wow fadeInUp" data-wow-delay=".9s"
                                id="registerSubmitBtn">
                                {{ __('auth.create_account') }}
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </button>

                            <div class="text-center wow fadeInUp" data-wow-delay="1s">
                                <p class="mb-0">
                                    {{ __('auth.already_have_account') }}
                                    <a href="{{ route('login') }}" class="text-primary fw-bold">{{ __('auth.sign_in') }}</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const togglePassword = document.getElementById('togglePassword');
                const password = document.getElementById('password');

                const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
                const passwordConfirm = document.getElementById('password_confirmation');

                if (togglePassword) {
                    togglePassword.addEventListener('click', function () {
                        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                        password.setAttribute('type', type);
                        this.setAttribute('aria-pressed', String(type === 'text'));
                        this.setAttribute('aria-label', type === 'text' ? 'Hide password' : 'Show password');
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                if (togglePasswordConfirm) {
                    togglePasswordConfirm.addEventListener('click', function () {
                        const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordConfirm.setAttribute('type', type);
                        this.setAttribute('aria-pressed', String(type === 'text'));
                        this.setAttribute('aria-label', type === 'text' ? 'Hide password confirmation' : 'Show password confirmation');
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                const registerForm = document.querySelector('.mhq-register-form');
                const submitBtn = document.getElementById('registerSubmitBtn');
                if (registerForm && submitBtn) {
                    registerForm.addEventListener('submit', function () {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '{{ __('auth.creating') }} <i class="fa fa-spinner fa-spin"></i>';
                    });
                }

                const photoInput = document.getElementById('profile_photo');
                const photoPreview = document.getElementById('registerPhotoPreview');
                const photoPlaceholder = document.getElementById('registerPhotoPlaceholder');
                if (photoInput && photoPreview) {
                    photoInput.addEventListener('change', function (e) {
                        const file = e.target.files && e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function (evt) {
                                photoPreview.src = evt.target.result;
                                photoPreview.style.display = 'block';
                                if (photoPlaceholder) photoPlaceholder.style.display = 'none';
                            };
                            reader.readAsDataURL(file);
                        } else {
                            photoPreview.style.display = 'none';
                            if (photoPlaceholder) photoPlaceholder.style.display = 'inline-block';
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
