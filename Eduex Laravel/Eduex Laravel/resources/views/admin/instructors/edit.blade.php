@extends('layouts.admin')

@section('title', 'Edit Instructor')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.instructors.index') }}">Instructors</a></div>
        <div class="breadcrumb-item active">Edit {{ $instructor->name }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Instructor</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.instructors.show', $instructor->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.instructors.update', $instructor->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $instructor->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $instructor->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" id="phone" name="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $instructor->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialization">Specialization</label>
                                    <input type="text" id="specialization" name="specialization"
                                        class="form-control @error('specialization') is-invalid @enderror"
                                        value="{{ old('specialization', $instructor->specialization) }}">
                                    @error('specialization')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="years_experience">Years of Experience</label>
                                    <input type="number" min="0" id="years_experience" name="years_experience"
                                        class="form-control @error('years_experience') is-invalid @enderror"
                                        value="{{ old('years_experience', $instructor->years_experience) }}">
                                    @error('years_experience')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">New Password</label>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Leave blank to keep current password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation">Confirm Password</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="Confirm new password">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bio">Short Bio</label>
                                    <textarea id="bio" name="bio" rows="4" class="form-control @error('bio') is-invalid @enderror"
                                        placeholder="Tell us about the instructor...">{{ old('bio', $instructor->bio) }}</textarea>
                                    @error('bio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="linkedin">LinkedIn</label>
                                    <input type="url" id="linkedin" name="linkedin" class="form-control"
                                        value="{{ old('linkedin', $instructor->linkedin) }}"
                                        placeholder="https://linkedin.com/in/...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="twitter">Twitter</label>
                                    <input type="url" id="twitter" name="twitter" class="form-control"
                                        value="{{ old('twitter', $instructor->twitter) }}"
                                        placeholder="https://twitter.com/...">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="facebook">Facebook</label>
                                    <input type="url" id="facebook" name="facebook" class="form-control"
                                        value="{{ old('facebook', $instructor->facebook) }}"
                                        placeholder="https://facebook.com/...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="youtube">YouTube</label>
                                    <input type="url" id="youtube" name="youtube" class="form-control"
                                        value="{{ old('youtube', $instructor->youtube) }}"
                                        placeholder="https://youtube.com/...">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_approved">Approval Status</label>
                                    <select id="is_approved" name="is_approved" class="form-control">
                                        <option value="1"
                                            {{ old('is_approved', $instructor->is_approved) ? 'selected' : '' }}>Approved
                                        </option>
                                        <option value="0"
                                            {{ old('is_approved', $instructor->is_approved) ? '' : 'selected' }}>Pending /
                                            Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="approval_reason">Approval Notes</label>
                                    <input type="text" id="approval_reason" name="approval_reason"
                                        class="form-control"
                                        value="{{ old('approval_reason', $instructor->approval_reason) }}"
                                        placeholder="Optional notes when approving">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="rejection_reason">Rejection Reason</label>
                                    <input type="text" id="rejection_reason" name="rejection_reason"
                                        class="form-control"
                                        value="{{ old('rejection_reason', $instructor->rejection_reason) }}"
                                        placeholder="Optional rejection reason">
                                </div>
                            </div>
                        </div>

                        <div class="form-group d-flex justify-content-between">
                            <a href="{{ route('admin.instructors.show', $instructor->id) }}"
                                class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
