@extends('layouts.student')

@section('content')
    <section class="section-bg fix section-padding">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="dashboard-header wow fadeInUp">
                        <div class="section-title-area">
                            <div class="section-title">
                                <h6>{{ __('student.settings') }}</h6>
                                <h2>{{ __('student.account_settings') }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Settings Section -->
                    <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.1s">
                        <div class="section-title">
                            <h3>{{ __('student.profile_information') }}</h3>
                            <p class="section-subtitle">{{ __('student.update_profile_desc') }}</p>
                        </div>

                        <form class="settings-form" id="profileForm" action="{{ route('student.settings.update') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf

                            <!-- Avatar Upload -->
                            <div class="form-group avatar-upload-group">
                                <label>{{ __('student.profile_picture') }}</label>
                                <div class="avatar-upload-wrapper">
                                    <div class="avatar-preview position-relative" style="cursor: pointer;">
                                        @if ($user->hasProfilePhoto())
                                            <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                                alt="{{ $user->name }}" id="avatarPreview" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                                        @else
                                            <img src="" alt="{{ $user->name }}" id="avatarPreview" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:none;">
                                            <div id="avatarInitialsFallback" class="d-flex align-items-center justify-content-center text-white fw-bold fs-3" style="width:100%; height:100%; background: linear-gradient(135deg, #6D1A95, #3B0764); border-radius: 50%;">
                                                {{ $user->initials }}
                                            </div>
                                        @endif
                                        <div class="avatar-overlay">
                                            <i class="fa-solid fa-camera"></i>
                                            <span>{{ $user->hasProfilePhoto() ? __('student.change_photo') : 'Upload Photo' }}</span>
                                        </div>
                                    </div>
                                    <input type="file" name="profile_photo" id="avatarInput" accept="image/jpeg,image/png,image/jpg,image/webp"
                                        style="display: none;">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="$('#avatarInput').click();" style="font-size: 12px; border-color: #6D1A95; color: #6D1A95;">
                                            <i class="fa-solid fa-camera me-1"></i> {{ $user->hasProfilePhoto() ? 'Choose New Photo' : 'Choose Profile Picture' }}
                                        </button>
                                        <small class="form-hint d-block mt-1">{{ __('student.recommended_size') }} (JPEG, PNG, WebP up to 10MB)</small>
                                    </div>
                                    @if (!$user->hasProfilePhoto())
                                        <div class="alert py-2 px-3 mt-2 mb-0 rounded-3" style="font-size: 12px; background: #F3E8FF; border: 1px solid #D8B4FE; color: #4A0E66;">
                                            <i class="fa-solid fa-circle-info me-1"></i> <strong>Photo Needed:</strong> You do not have an active profile photo yet. Upload a real picture so attendees and instructors recognize you.
                                        </div>
                                    @endif
                                </div>
                                @error('profile_photo')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Basic Information -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>{{ __('student.full_name') }} <span class="required">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $user->name) }}" required placeholder="{{ __('student.enter_full_name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>{{ __('auth.email') }} <span class="required">*</span></label>
                                    <input type="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $user->email) }}" required placeholder="{{ __('auth.enter_email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>{{ __('student.phone_number') }}</label>
                                    <input type="tel" name="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $user->phone) }}" placeholder="{{ __('instructor.enter_phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="button" class="cancel-btn"
                                    onclick="document.getElementById('profileForm').reset()">
                                    {{ __('frontend.cancel') }}
                                </button>
                                <button type="submit" class="theme-btn style-2 px-5">
                                    <i class="fa-solid fa-save"></i>
                                    {{ __('student.save_changes') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Password Change Section -->
                    <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.2s" style="margin-top: 30px;">
                        <div class="section-title">
                            <h3>{{ __('student.change_password') }}</h3>
                            <p class="section-subtitle">{{ __('student.update_password_desc') }}</p>
                        </div>

                        <form class="settings-form" id="passwordForm" action="{{ route('student.settings.update') }}"
                            method="POST">
                            @csrf

                            <div class="form-group">
                                <label>{{ __('student.current_password') }}</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="current_password"
                                        class="form-control @error('current_password') is-invalid @enderror"
                                        placeholder="{{ __('student.enter_current_password') }}" id="currentPassword">
                                    <button type="button" class="password-toggle"
                                        onclick="togglePassword('currentPassword', this)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-hint">{{ __('student.leave_blank_password') }}</small>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>{{ __('student.new_password') }}</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="{{ __('student.enter_new_password') }}" id="newPassword">
                                        <button type="button" class="password-toggle"
                                            onclick="togglePassword('newPassword', this)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-hint">{{ __('student.min_password_length') }}</small>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>{{ __('student.confirm_new_password') }}</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="password_confirmation" class="form-control"
                                            placeholder="{{ __('student.confirm_new_password') }}" id="confirmPassword">
                                        <button type="button" class="password-toggle"
                                            onclick="togglePassword('confirmPassword', this)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="cancel-btn"
                                    onclick="document.getElementById('passwordForm').reset()">
                                    {{ __('frontend.cancel') }}
                                </button>
                                <button type="submit" class="theme-btn style-2 px-5">
                                    <i class="fa-solid fa-key"></i>
                                    {{ __('student.update_password_btn') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Settings Section -->
                    <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.3s" style="margin-top: 30px;">
                        <div class="section-title">
                            <h3>{{ __('student.account_settings') }}</h3>
                            <p class="section-subtitle">{{ __('student.manage_preferences') }}</p>
                        </div>

                        <div class="account-settings-list">
                            <form action="{{ route('student.settings.notifications.update') }}" method="POST"
                                id="studentNotificationForm">
                                @csrf
                                @method('PUT')

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('student.email_notifications') }}</h4>
                                        <p>{{ __('student.email_notifications_desc') }}</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="hidden" name="email_notifications" value="0">
                                            <input type="checkbox" class="notification-toggle" name="email_notifications"
                                                value="1"
                                                {{ data_get($notificationSettings, 'email_notifications', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('student.course_reminders') }}</h4>
                                        <p>{{ __('student.course_reminders_desc') }}</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="hidden" name="course_reminders" value="0">
                                            <input type="checkbox" class="notification-toggle" name="course_reminders"
                                                value="1"
                                                {{ data_get($notificationSettings, 'course_reminders', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('student.progress_reports') }}</h4>
                                        <p>{{ __('student.progress_reports_desc') }}</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="hidden" name="progress_reports" value="0">
                                            <input type="checkbox" class="notification-toggle" name="progress_reports"
                                                value="1"
                                                {{ data_get($notificationSettings, 'progress_reports', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('student.marketing_emails') }}</h4>
                                        <p>{{ __('student.marketing_emails_desc') }}</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="hidden" name="marketing_emails" value="0">
                                            <input type="checkbox" class="notification-toggle" name="marketing_emails"
                                                value="1"
                                                {{ data_get($notificationSettings, 'marketing_emails', false) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="dashboard-courses-section danger-zone wow fadeInUp" data-wow-delay="0.4s"
                        style="margin-top: 30px;">
                        <div class="section-title">
                            <h3 style="color: #ef4444;">{{ __('student.danger_zone') }}</h3>
                            <p class="section-subtitle">{{ __('student.destructive_actions') }}</p>
                        </div>

                        <div class="danger-actions">
                            @isset($latestDeletionRequest)
                                @php($deletionStatus = $latestDeletionRequest->status)
                                <div class="alert {{ $deletionStatus === 'pending' ? 'alert-warning' : ($deletionStatus === 'rejected' ? 'alert-danger' : 'alert-success') }} mb-4"
                                    role="alert">
                                    @if ($deletionStatus === 'pending')
                                        <strong>{{ __('student.awaiting_approval') }}:</strong> Your account deletion request was submitted on
                                        {{ $latestDeletionRequest->created_at->format('M d, Y') }}. We'll notify you once an
                                        admin reviews it.
                                    @elseif($deletionStatus === 'rejected')
                                        <strong>Request rejected:</strong>
                                        {{ $latestDeletionRequest->note ?? 'Please contact support for more details.' }}
                                    @elseif($deletionStatus === 'approved')
                                        <strong>Approved:</strong> This request has already been processed.
                                    @endif
                                </div>
                            @endisset
                            <div class="danger-item">
                                <div class="danger-info">
                                    <h4>{{ __('student.delete_account') }}</h4>
                                    <p>{{ __('student.delete_account_desc') }}</p>
                                </div>
                                @if (isset($deletionStatus) && $deletionStatus === 'pending')
                                    <button type="button" class="danger-btn" disabled>
                                        <i class="fa-solid fa-clock"></i>
                                        {{ __('student.awaiting_approval') }}
                                    </button>
                                @else
                                    <button type="button" class="danger-btn" onclick="confirmDeleteAccount()">
                                        <i class="fa-solid fa-trash"></i>
                                        {{ __('student.delete_account') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <form id="student-delete-account-form" action="{{ route('student.settings.account.destroy') }}"
                            method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Avatar Preview
                $('#avatarInput').on('change', function(e) {
                    const file = e.target.files && e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(evt) {
                            $('#avatarPreview').attr('src', evt.target.result).show();
                            $('#avatarInitialsFallback').hide();
                        };
                        reader.readAsDataURL(file);
                    }
                });

                $('.avatar-preview').on('click', function() {
                    $('#avatarInput').click();
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const notificationForm = document.getElementById('studentNotificationForm');

                if (notificationForm) {
                    notificationForm.addEventListener('change', function(event) {
                        if (event.target.classList.contains('notification-toggle')) {
                            notificationForm.submit();
                        }
                    });
                }
            });

            function togglePassword(inputId, button) {
                const input = document.getElementById(inputId);
                const icon = button.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            function confirmDeleteAccount() {
                const form = document.getElementById('student-delete-account-form');

                if (!form) {
                    return;
                }

                Swal.fire({
                    title: "{{ __('student.delete_account_title') }}",
                    text: "{{ __('student.delete_account_desc') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('student.yes_delete_it') }}",
                    cancelButtonText: "{{ __('frontend.cancel') }}",
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#1d4ed8'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "{{ __('student.submitting_request') }}",
                            text: "{{ __('student.please_wait_deletion') }}",
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                                form.submit();
                            }
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection
