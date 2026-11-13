@extends('layouts.admin')

@section('title', 'Edit Profile')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Profile</div>
    </div>
@endsection

@section('main-content')
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="section-title mb-1">Manage Profile</h2>
                    <p class="section-lead mb-0 mt-2">
                        Update your personal information, account credentials, and profile photo.
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Account Information</h4>
                    </div>
                    <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                    class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                    class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                    class="form-control @error('phone') is-invalid @enderror" placeholder="+1 555 123 4567">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="profile_photo">Profile Photo</label>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        @if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo))
                                            <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                                alt="Profile photo preview" class="rounded-circle"
                                                style="width: 80px; height: 80px; object-fit: cover;">
                                        @else
                                            <img src="{{ asset('assets/images/avatar-1.png') }}" alt="Default avatar"
                                                class="rounded-circle"
                                                style="width: 80px; height: 80px; object-fit: cover;">
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="custom-file">
                                            <input type="file" name="profile_photo" id="profile_photo"
                                                class="custom-file-input @error('profile_photo') is-invalid @enderror"
                                                accept="image/*">
                                            <label class="custom-file-label" for="profile_photo">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">
                                            Accepted formats: JPG, PNG. Max size: 2MB.
                                        </small>
                                        @error('profile_photo')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Security</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Update your password to keep your account secure. Leave the fields blank to keep the current
                            password.
                        </p>
                        <div class="form-group mb-0">
                            <form action="{{ route('admin.profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
                                <input type="hidden" name="email" value="{{ old('email', $user->email) }}">
                                <input type="hidden" name="phone" value="{{ old('phone', $user->phone) }}">
                                <div class="form-group">
                                    <label for="password">New Password</label>
                                    <input type="password" name="password" id="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        autocomplete="new-password" placeholder="Enter new password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="password_confirmation">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-control" autocomplete="new-password"
                                        placeholder="Confirm new password">
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-lock mr-1"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('profile_photo');
            if (fileInput) {
                fileInput.addEventListener('change', function(event) {
                    const label = event.target.nextElementSibling;
                    if (label && event.target.files.length > 0) {
                        label.textContent = event.target.files[0].name;
                    }
                });
            }
        });
    </script>
@endpush
