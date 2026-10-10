@extends('layouts.instructor')

@section('content')
    <!-- Become Instructor Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover"
        style="background-image: url('{{ asset('assets/front/img/home-1/hero/hero-bg.jpg') }}');">
        <div class="hero-shape-1 float-bob-x">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-01.png') }}" alt="img">
        </div>
        <div class="hero-shape-2 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-02.png') }}" alt="img">
        </div>
        <div class="hero-shape-3 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-03.png') }}" alt="img">
        </div>
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="instructor-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="instructor-header text-center mb-4">
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('instructor.become_instructor') }}</h2>
                            <p class="wow fadeInUp" data-wow-delay=".3s">{{ __('instructor.become_instructor_subtitle') }}
                            </p>
                        </div>

                        <form action="{{ route('instructor.register.submit') }}" method="POST" class="instructor-form"
                            enctype="multipart/form-data">
                            @csrf
                            <!-- Personal Information Section -->
                            <h4 class="mb-4 wow fadeInUp" data-wow-delay=".4s">{{ __('instructor.personal_info') }}</h4>
                            <div class="row">
                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".5s">
                                    <label for="first_name" class="form-label">{{ __('instructor.first_name') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <input type="text" name="first_name" id="first_name"
                                            value="{{ old('first_name') }}" placeholder="{{ __('instructor.enter_first_name') }}"
                                            class="@error('first_name') is-invalid @enderror" required
                                            autocomplete="given-name" autofocus>
                                    </div>
                                    @error('first_name')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".5s">
                                    <label for="last_name" class="form-label">{{ __('instructor.last_name') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}"
                                            placeholder="{{ __('instructor.enter_last_name') }}"
                                            class="@error('last_name') is-invalid @enderror" required
                                            autocomplete="family-name">
                                    </div>
                                    @error('last_name')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".6s">
                                    <label for="email" class="form-label">{{ __('instructor.email_address') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                                            placeholder="{{ __('instructor.enter_email') }}" class="@error('email') is-invalid @enderror"
                                            required autocomplete="email">
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".7s">
                                    <label for="phone" class="form-label">{{ __('instructor.phone_number') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                            placeholder="{{ __('instructor.enter_phone') }}"
                                            class="@error('phone') is-invalid @enderror" required autocomplete="tel">
                                    </div>
                                    @error('phone')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".8s">
                                    <label for="password" class="form-label">{{ __('instructor.password') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt position-relative">
                                        <input type="password" name="password" id="password"
                                            placeholder="{{ __('instructor.enter_password') }}"
                                            class="@error('password') is-invalid @enderror" required
                                            autocomplete="new-password">
                                        <span class="toggle-password position-absolute">
                                            <i class="fa-regular fa-eye" id="togglePassword"></i>
                                        </span>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-2">{{ __('instructor.password_hint') }}</small>
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".9s">
                                    <label for="password_confirmation" class="form-label">{{ __('instructor.confirm_password') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt position-relative">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            placeholder="{{ __('instructor.confirm_password_placeholder') }}" required autocomplete="new-password">
                                        <span class="toggle-password-confirm position-absolute">
                                            <i class="fa-regular fa-eye" id="togglePasswordConfirm"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="1s">
                                    <label for="bio" class="form-label">{{ __('instructor.bio') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <textarea name="bio" id="bio"
                                            placeholder="{{ __('instructor.bio_placeholder') }}"
                                            class="@error('bio') is-invalid @enderror" required rows="5">{{ old('bio') }}</textarea>
                                        <div class="instructor-char-counter text-muted mt-2" data-max-length="500">0 / 500
                                        </div>
                                    </div>
                                    @error('bio')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="instructor-section-divider mt-4 mb-4"></div>

                            <!-- Professional Experience Section -->
                            <h4 class="mb-4 wow fadeInUp" data-wow-delay="1.1s">{{ __('instructor.professional_experience') }}</h4>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="1.2s">
                                    <label for="years_experience" class="form-label">{{ __('instructor.years_experience') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <select name="years_experience" id="years_experience"
                                            class="@error('years_experience') is-invalid @enderror" required>
                                            <option value="">{{ __('instructor.select_years') }}</option>
                                            <option value="0-1"
                                                {{ old('years_experience') == '0-1' ? 'selected' : '' }}>{{ __('instructor.years_0_1') }}</option>
                                            <option value="2-5"
                                                {{ old('years_experience') == '2-5' ? 'selected' : '' }}>{{ __('instructor.years_2_5') }}</option>
                                            <option value="6-10"
                                                {{ old('years_experience') == '6-10' ? 'selected' : '' }}>{{ __('instructor.years_6_10') }}
                                            </option>
                                            <option value="10+"
                                                {{ old('years_experience') == '10+' ? 'selected' : '' }}>{{ __('instructor.years_10_plus') }}</option>
                                        </select>
                                    </div>
                                    @error('years_experience')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="1.2s">
                                    <label for="specialization" class="form-label">{{ __('instructor.specialization') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <input type="text" name="specialization" id="specialization"
                                            value="{{ old('specialization') }}"
                                            placeholder="{{ __('instructor.specialization_placeholder') }}"
                                            class="@error('specialization') is-invalid @enderror" required>
                                    </div>
                                    @error('specialization')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="1.3s">
                                    <label for="previous_experience" class="form-label">{{ __('instructor.previous_experience') }} <span class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <textarea name="previous_experience" id="previous_experience"
                                            placeholder="{{ __('instructor.previous_experience_placeholder') }}"
                                            class="@error('previous_experience') is-invalid @enderror" required rows="5">{{ old('previous_experience') }}</textarea>
                                    </div>
                                    @error('previous_experience')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="instructor-section-divider mt-4 mb-4"></div>

                            <!-- Education Background Section -->
                            <h4 class="mb-4 wow fadeInUp" data-wow-delay="1.4s">{{ __('instructor.education_background') }}</h4>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="1.5s">
                                    <label for="highest_degree" class="form-label">{{ __('instructor.highest_degree') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <select name="highest_degree" id="highest_degree"
                                            class="@error('highest_degree') is-invalid @enderror" required>
                                            <option value="">{{ __('instructor.select_degree') }}</option>
                                            <option value="High School"
                                                {{ old('highest_degree') == 'High School' ? 'selected' : '' }}>{{ __('instructor.degree_high_school') }}
                                            </option>
                                            <option value="Bachelor's"
                                                {{ old('highest_degree') == "Bachelor's" ? 'selected' : '' }}>{{ __('instructor.degree_bachelors') }}</option>
                                            <option value="Master's"
                                                {{ old('highest_degree') == "Master's" ? 'selected' : '' }}>{{ __('instructor.degree_masters') }}
                                            </option>
                                            <option value="PhD" {{ old('highest_degree') == 'PhD' ? 'selected' : '' }}>{{ __('instructor.degree_phd') }}</option>
                                            <option value="Other"
                                                {{ old('highest_degree') == 'Other' ? 'selected' : '' }}>{{ __('instructor.degree_other') }}</option>
                                        </select>
                                    </div>
                                    @error('highest_degree')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="1.5s">
                                    <label for="field_of_study" class="form-label">{{ __('instructor.field_of_study') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <input type="text" name="field_of_study" id="field_of_study"
                                            value="{{ old('field_of_study') }}"
                                            placeholder="{{ __('instructor.field_of_study_placeholder') }}"
                                            class="@error('field_of_study') is-invalid @enderror" required>
                                    </div>
                                    @error('field_of_study')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="1.6s">
                                    <label for="certifications" class="form-label">{{ __('instructor.certifications') }}</label>
                                    <div class="instructor-form-clt">
                                        <textarea name="certifications" id="certifications" placeholder="{{ __('instructor.certifications_placeholder') }}"
                                            rows="4">{{ old('certifications') }}</textarea>
                                        <small class="text-muted">{{ __('instructor.certifications_hint') }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="instructor-section-divider mt-4 mb-4"></div>

                            <!-- Documents Section -->
                            <h4 class="mb-4 wow fadeInUp" data-wow-delay="1.7s">{{ __('instructor.documents_profile') }}</h4>

                            <div class="row">
                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="1.8s">
                                    <label class="form-label">{{ __('instructor.profile_photo') }} <span class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <div class="instructor-file-upload">
                                            <input type="file" name="profile_photo" id="profile_photo"
                                                accept="image/*" class="@error('profile_photo') is-invalid @enderror"
                                                required>
                                            <div class="instructor-file-upload-label">
                                                <div class="instructor-file-upload-icon">
                                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                                </div>
                                                <div class="instructor-file-upload-text">{{ __('instructor.upload_file_hint') }}</div>
                                                <div class="instructor-file-upload-hint">{{ __('instructor.photo_upload_hint') }}</div>
                                            </div>
                                            <div class="instructor-file-preview"></div>
                                        </div>
                                    </div>
                                    @error('profile_photo')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="1.9s">
                                    <label class="form-label">{{ __('instructor.resume_cv') }} <span class="text-danger">*</span></label>
                                    <div class="instructor-form-clt">
                                        <div class="instructor-file-upload">
                                            <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx"
                                                class="@error('resume') is-invalid @enderror" required>
                                            <div class="instructor-file-upload-label">
                                                <div class="instructor-file-upload-icon">
                                                    <i class="fa-solid fa-file-arrow-up"></i>
                                                </div>
                                                <div class="instructor-file-upload-text">{{ __('instructor.upload_file_hint') }}</div>
                                                <div class="instructor-file-upload-hint">{{ __('instructor.resume_upload_hint') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @error('resume')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="1.95s">
                                    <x-recaptcha-widget class="mb-3" action="instructor_register" />
                                </div>

                                <div class="col-lg-12 wow fadeInUp" data-wow-delay="2s">
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" name="terms" id="terms"
                                            required>
                                        <label class="form-check-label" for="terms">
                                            {{ __('instructor.i_agree_to') }} <a href="#" class="text-primary">{{ __('instructor.terms_of_service') }}</a> {{ __('instructor.and') }}
                                            <a href="#" class="text-primary">{{ __('instructor.privacy_policy') }}</a> <span
                                                class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <button type="submit" class="theme-btn me-2 wow fadeInUp" data-wow-delay="2.1s"
                                        id="instructorSubmitBtn">
                                        {{ __('instructor.submit_application') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </button>
                                    <p class="mt-3 mb-0">
                                        {{ __('instructor.already_have_account') }}
                                        <a href="{{ route('login') }}" class="text-primary fw-bold">{{ __('instructor.sign_in') }}</a>
                                    </p>
                                </div>
                            </div>
                        </form>

                        <!-- Why Become An Instructor Box - Moved to Bottom -->
                        <div class="instructor-info-box wow fadeInUp mt-4" data-wow-delay="2.2s">
                            <h5><i class="fa-solid fa-info-circle me-2"></i> {{ __('instructor.why_become_instructor') }}</h5>
                            <p>{{ __('instructor.join_instructors_desc') }}</p>
                            <ul>
                                <li>{{ __('instructor.reach_students') }}</li>
                                <li>{{ __('instructor.set_schedule') }}</li>
                                <li>{{ __('instructor.earn_money') }}</li>
                                <li>{{ __('instructor.build_brand') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const togglePassword = document.getElementById('togglePassword');
                const password = document.getElementById('password');

                const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
                const passwordConfirm = document.getElementById('password_confirmation');

                if (togglePassword) {
                    togglePassword.addEventListener('click', function() {
                        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                        password.setAttribute('type', type);
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                if (togglePasswordConfirm) {
                    togglePasswordConfirm.addEventListener('click', function() {
                        const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordConfirm.setAttribute('type', type);
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                const instructorForm = document.querySelector('.instructor-form');
                const submitBtn = document.getElementById('instructorSubmitBtn');
                if (instructorForm && submitBtn) {
                    instructorForm.addEventListener('submit', function() {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '{{ __('instructor.submitting') }} <i class="fa fa-spinner fa-spin"></i>';
                    });
                }
            });
        </script>
    @endpush
@endsection
