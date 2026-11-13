@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <!-- Page Header -->
                <div class="dashboard-header wow fadeInUp">
                    <div class="section-title-area text-left">
                        <div class="section-title">
                            <h6>Live Classes Module</h6>
                            <h2>Schedule Live Class</h2>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.1s" style="margin-top: 30px;">
                    <form class="settings-form" action="{{ route('instructor.live-classes.store') }}" method="POST" style="background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #f1f5f9;">
                        @csrf

                        <!-- Course Select -->
                        <div class="form-group mb-4">
                            <label for="course_id" style="font-weight: 600; display: block; margin-bottom: 8px;">Select Course <span class="text-danger">*</span></label>
                            <select id="course_id" name="course_id" class="form-control @error('course_id') is-invalid @enderror" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="">-- Choose Course --</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Select the course you want to link this live class to.</small>
                            @error('course_id')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Lesson Select -->
                        <div class="form-group mb-4">
                            <label for="lesson_id" style="font-weight: 600; display: block; margin-bottom: 8px;">Select Live Lesson (Optional)</label>
                            <select id="lesson_id" name="lesson_id" class="form-control @error('lesson_id') is-invalid @enderror" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="">-- Choose Live Lesson (or leave unlinked) --</option>
                            </select>
                            <small class="form-hint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Link this live class session to an existing live lesson in the curriculum. Only lessons marked as "Live" in the course builder will appear here.</small>
                            @error('lesson_id')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Title -->
                        <div class="form-group mb-4">
                            <label for="title" style="font-weight: 600; display: block; margin-bottom: 8px;">Live Class Title <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. Q&A and Interactive Review Session" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            @error('title')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="form-group mb-4">
                            <label for="description" style="font-weight: 600; display: block; margin-bottom: 8px;">Description / Agenda (Optional)</label>
                            <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Provide details about what will be covered in this session..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Join URL -->
                        <div class="form-group mb-4">
                            <label for="join_url" style="font-weight: 600; display: block; margin-bottom: 8px;">Live Meeting URL (Zoom/Meet/etc.) <span class="text-danger">*</span></label>
                            <input type="url" id="join_url" name="join_url" class="form-control @error('join_url') is-invalid @enderror" value="{{ old('join_url') }}" placeholder="e.g. https://zoom.us/j/... or https://meet.google.com/..." required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <small class="form-hint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">The actual video URL students will click to join the live session.</small>
                            @error('join_url')
                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Scheduled At & Duration -->
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;" class="mb-5">
                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="scheduled_at" style="font-weight: 600; display: block; margin-bottom: 8px;">Scheduled Start Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror" value="{{ old('scheduled_at') }}" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @error('scheduled_at')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="flex: 1; min-width: 250px;">
                                <label for="duration_minutes" style="font-weight: 600; display: block; margin-bottom: 8px;">Duration (Minutes) <span class="text-danger">*</span></label>
                                <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="1440" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', 60) }}" required placeholder="e.g. 60" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @error('duration_minutes')
                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-actions" style="border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: right;">
                            <a href="{{ route('instructor.live-classes.index') }}" class="theme-btn style-2" style="background-color: #cbd5e1; color: #334155; margin-right: 10px; padding: 10px 20px; border-radius: 4px;">Cancel</a>
                            <button type="submit" class="theme-btn style-2" style="padding: 10px 20px; border-radius: 4px;">Schedule Class</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('course_id');
    const lessonSelect = document.getElementById('lesson_id');

    function loadLessons(courseId, selectedLessonId = null) {
        if (!courseId) {
            lessonSelect.innerHTML = '<option value="">-- Choose Live Lesson (or leave unlinked) --</option>';
            return;
        }

        lessonSelect.innerHTML = '<option value="">Loading lessons...</option>';

        fetch(`/instructor/live-classes/lessons?course_id=${courseId}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">-- Choose Live Lesson (or leave unlinked) --</option>';
                if (data.lessons && data.lessons.length > 0) {
                    data.lessons.forEach(lesson => {
                        const selected = (selectedLessonId && selectedLessonId == lesson.id) ? 'selected' : '';
                        html += `<option value="${lesson.id}" ${selected}>${lesson.title}</option>`;
                    });
                } else {
                    html = '<option value="">No Live Lessons found in curriculum</option>';
                }
                lessonSelect.innerHTML = html;
            })
            .catch(err => {
                console.error('Error fetching lessons:', err);
                lessonSelect.innerHTML = '<option value="">Error loading lessons</option>';
            });
    }

    // Trigger on change
    courseSelect.addEventListener('change', function() {
        loadLessons(this.value);
    });

    // Initial load if course is selected (e.g., validation failure redirect back)
    if (courseSelect.value) {
        loadLessons(courseSelect.value, "{{ old('lesson_id') }}");
    }
});
</script>
@endsection
