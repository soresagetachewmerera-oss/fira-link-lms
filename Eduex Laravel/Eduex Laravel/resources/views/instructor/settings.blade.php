@extends('layouts.instructor')

@section('content')
    <section class="section-bg fix section-padding">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="dashboard-header wow fadeInUp">
                        <div class="section-title-area">
                            <div class="section-title">
                                <h6>{{ __('instructor.settings') }}</h6>
                                <h2>{{ __('instructor.account_settings') }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Settings Section -->
                    <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.1s">
                        <div class="section-title">
                            <h3>{{ __('instructor.profile_info') }}</h3>
                            <p class="section-subtitle">{{ __('instructor.profile_info_desc') }}</p>
                        </div>

                        <form class="settings-form" id="profileForm"
                            action="{{ route('instructor.settings.profile.update') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- Avatar Upload -->
                            <div class="form-group avatar-upload-group">
                                <label>{{ __('instructor.profile_picture') }}</label>
                                <div class="avatar-upload-wrapper">
                                    <div class="avatar-preview">
                                        <img src="{{ $profile['avatar'] }}" alt="{{ $profile['name'] }}" id="avatarPreview">
                                        <div class="avatar-overlay">
                                            <i class="fa-solid fa-camera"></i>
                                            <span>{{ __('instructor.change_photo') }}</span>
                                        </div>
                                    </div>
                                    <input type="file" name="avatar" id="avatarInput" accept="image/*"
                                        style="display: none;">
                                    <small class="form-hint">{{ __('instructor.avatar_hint') }}</small>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>{{ __('instructor.full_name') }} <span class="required">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ $profile['name'] }}"
                                        required placeholder="{{ __('instructor.enter_full_name') }}">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('instructor.email_address') }} <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-control"
                                        value="{{ $profile['email'] }}" required placeholder="{{ __('instructor.enter_email') }}">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>{{ __('instructor.phone_number') }}</label>
                                    <input type="tel" name="phone" class="form-control"
                                        value="{{ $profile['phone'] }}" placeholder="{{ __('instructor.enter_phone') }}">
                                </div>
                            </div>

                            <!-- Bio -->
                            <div class="form-group">
                                <label>{{ __('instructor.bio_label') }}</label>
                                <textarea name="bio" class="form-control" rows="5"
                                    placeholder="{{ __('instructor.bio_placeholder') }}">{{ $profile['bio'] }}</textarea>
                                <small class="form-hint">{{ __('instructor.bio_hint') }}</small>
                            </div>


                            <!-- Social Media Links -->
                            <div class="form-section-divider">
                                <h4>{{ __('instructor.social_links') }}</h4>
                                <p class="section-subtitle">{{ __('instructor.social_links_desc') }}</p>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fa-brands fa-linkedin"></i> LinkedIn
                                    </label>
                                    <input type="url" name="linkedin" class="form-control"
                                        value="{{ $profile['linkedin'] }}" placeholder="https://linkedin.com/in/username">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fa-brands fa-twitter"></i> Twitter
                                    </label>
                                    <input type="url" name="twitter" class="form-control"
                                        value="{{ $profile['twitter'] }}" placeholder="https://twitter.com/username">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fa-brands fa-facebook"></i> Facebook
                                    </label>
                                    <input type="url" name="facebook" class="form-control"
                                        value="{{ $profile['facebook'] }}" placeholder="https://facebook.com/username">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fa-brands fa-youtube"></i> YouTube
                                    </label>
                                    <input type="url" name="youtube" class="form-control"
                                        value="{{ $profile['youtube'] }}" placeholder="https://youtube.com/@username">
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="button" class="cancel-btn"
                                    onclick="document.getElementById('profileForm').reset()">
                                    {{ __('instructor.cancel_btn') }}
                                </button>
                                <button type="submit" class="theme-btn style-2 px-5">
                                    <i class="fa-solid fa-save"></i>
                                    {{ __('instructor.save_changes') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Password Change Section -->
                    <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.2s" style="margin-top: 30px;">
                        <div class="section-title">
                            <h3>{{ __('instructor.change_password') }}</h3>
                            <p class="section-subtitle">{{ __('instructor.change_password_desc') }}</p>
                        </div>

                        <form class="settings-form" id="passwordForm"
                            action="{{ route('instructor.settings.password.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label>{{ __('instructor.current_password') }} <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="current_password" class="form-control" required
                                        placeholder="{{ __('instructor.enter_current_password') }}" id="currentPassword">
                                    <button type="button" class="password-toggle"
                                        onclick="togglePassword('currentPassword', this)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>{{ __('instructor.new_password') }} <span class="required">*</span></label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="new_password" class="form-control" required
                                            placeholder="{{ __('instructor.enter_new_password') }}" id="newPassword">
                                        <button type="button" class="password-toggle"
                                            onclick="togglePassword('newPassword', this)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-hint">{{ __('instructor.min_8_chars') }}</small>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('instructor.confirm_password') }} <span class="required">*</span></label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="new_password_confirmation" class="form-control"
                                            required placeholder="{{ __('instructor.confirm_password') }}" id="confirmPassword">
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
                                    {{ __('instructor.cancel_btn') }}
                                </button>
                                <button type="submit" class="theme-btn style-2 px-5">
                                    <i class="fa-solid fa-key"></i>
                                    {{ __('instructor.update_password') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Settings Section -->
                    <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.3s" style="margin-top: 30px;">
                        <div class="section-title">
                            <h3>{{ __('instructor.account_settings') }}</h3>
                            <p class="section-subtitle">{{ __('instructor.account_settings_desc') }}</p>
                        </div>

                        <div class="account-settings-list">
                            <form action="{{ route('instructor.settings.notifications.update') }}" method="POST"
                                id="instructorNotificationForm">
                                @csrf
                                @method('PUT')

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('instructor.email_notifications') }}</h4>
                                        <p>{{ __('instructor.email_notifications_desc') }}</p>
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
                                        <h4>{{ __('instructor.course_updates') }}</h4>
                                        <p>{{ __('instructor.course_updates_desc') }}</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="hidden" name="course_updates" value="0">
                                            <input type="checkbox" class="notification-toggle" name="course_updates"
                                                value="1"
                                                {{ data_get($notificationSettings, 'course_updates', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('instructor.review_notifications') }}</h4>
                                        <p>{{ __('instructor.review_notifications_desc') }}</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="hidden" name="review_notifications" value="0">
                                            <input type="checkbox" class="notification-toggle"
                                                name="review_notifications" value="1"
                                                {{ data_get($notificationSettings, 'review_notifications', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4>{{ __('instructor.marketing_emails') }}</h4>
                                        <p>{{ __('instructor.marketing_emails_desc') }}</p>
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
                            <h3 style="color: #ef4444;">{{ __('instructor.danger_zone') }}</h3>
                            <p class="section-subtitle">{{ __('instructor.danger_zone_desc') }}</p>
                        </div>

                        <div class="danger-actions">
                            @isset($latestDeletionRequest)
                                @php($deletionStatus = $latestDeletionRequest->status)
                                <div class="alert {{ $deletionStatus === 'pending' ? 'alert-warning' : ($deletionStatus === 'rejected' ? 'alert-danger' : 'alert-success') }} mb-4"
                                    role="alert">
                                    @if ($deletionStatus === 'pending')
                                        <strong>{{ __('instructor.awaiting_approval_label') }}</strong> {{ __('instructor.deletion_request_submitted', ['date' => $latestDeletionRequest->created_at->format('M d, Y')]) }}
                                    @elseif($deletionStatus === 'rejected')
                                        <strong>{{ __('instructor.request_rejected_label') }}</strong>
                                        {{ $latestDeletionRequest->note ?? __('instructor.contact_support_note') }}
                                    @elseif($deletionStatus === 'approved')
                                        <strong>{{ __('instructor.approved_label') }}</strong> {{ __('instructor.request_processed') }}
                                    @endif
                                </div>
                            @endisset
                            <div class="danger-item">
                                <div class="danger-info">
                                    <h4>{{ __('instructor.delete_account') }}</h4>
                                    <p>{{ __('instructor.delete_account_desc') }}</p>
                                </div>
                                @if (isset($deletionStatus) && $deletionStatus === 'pending')
                                    <button type="button" class="danger-btn" disabled>
                                        <i class="fa-solid fa-clock"></i>
                                        {{ __('instructor.awaiting_approval') }}
                                    </button>
                                @else
                                    <button type="button" class="danger-btn" onclick="confirmDeleteAccount()">
                                        <i class="fa-solid fa-trash"></i>
                                        {{ __('instructor.delete_account') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <form id="instructor-delete-account-form"
                            action="{{ route('instructor.settings.account.destroy') }}" method="POST" class="d-none">
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
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#avatarPreview').attr('src', e.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                });

                $('.avatar-preview').on('click', function() {
                    $('#avatarInput').click();
                });

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
                const form = document.getElementById('instructor-delete-account-form');

                if (!form) {
                    return;
                }

                Swal.fire({
                    title: '{{ __('instructor.delete_account_confirm_title') }}',
                    text: '{{ __('instructor.delete_account_confirm_text') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('instructor.yes_delete_it') }}',
                    cancelButtonText: '{{ __('instructor.cancel_btn') }}',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#1d4ed8'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Submitting request…',
                            text: 'Please wait while we submit your account deletion request for admin approval.',
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

            document.addEventListener('DOMContentLoaded', function() {
                const notificationForm = document.getElementById('instructorNotificationForm');

                if (notificationForm) {
                    notificationForm.addEventListener('change', function(event) {
                        if (event.target.classList.contains('notification-toggle')) {
                            notificationForm.submit();
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
