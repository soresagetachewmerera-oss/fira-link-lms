@extends('layouts.admin')

@section('title', 'Course Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Course Settings</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Course Moderation</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.course.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto_publish_courses"
                                    name="auto_publish_courses" value="1"
                                    {{ old('auto_publish_courses', $settings['auto_publish_courses'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="auto_publish_courses">Automatically publish new
                                    courses</label>
                            </div>
                            <small class="text-muted d-block mt-1">Disable this if you prefer to review all submissions
                                manually.</small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_review_for_updates"
                                    name="require_review_for_updates" value="1"
                                    {{ old('require_review_for_updates', $settings['require_review_for_updates'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="require_review_for_updates">Require review when
                                    instructors update courses</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="default_visibility">Default Course Visibility</label>
                            @php
                                $visibility = old('default_visibility', $settings['default_visibility'] ?? 'private');
                            @endphp
                            <select name="default_visibility" id="default_visibility"
                                class="form-control @error('default_visibility') is-invalid @enderror" required>
                                <option value="private" {{ $visibility === 'private' ? 'selected' : '' }}>Private (hidden
                                    until approved)</option>
                                <option value="public" {{ $visibility === 'public' ? 'selected' : '' }}>Public (visible
                                    immediately)</option>
                                <option value="unlisted" {{ $visibility === 'unlisted' ? 'selected' : '' }}>Unlisted
                                    (accessible via direct link)</option>
                            </select>
                            @error('default_visibility')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="max_topic_depth">Maximum Topic Depth</label>
                            <input type="number" name="max_topic_depth" id="max_topic_depth"
                                class="form-control @error('max_topic_depth') is-invalid @enderror"
                                value="{{ old('max_topic_depth', $settings['max_topic_depth'] ?? 3) }}" min="1"
                                max="10" required>
                            @error('max_topic_depth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Controls how many nesting levels are allowed inside the course
                                builder.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
