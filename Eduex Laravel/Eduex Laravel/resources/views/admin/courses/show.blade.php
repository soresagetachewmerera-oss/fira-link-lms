@extends('layouts.admin')

@section('title', 'Course Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Courses</a></div>
        <div class="breadcrumb-item">Course Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Course Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Courses
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Course Header -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            @if ($course->featured_image)
                                <img src="{{ asset('storage/' . $course->featured_image) }}" alt="{{ $course->title }}"
                                    class="img-fluid rounded" style="max-height: 300px; width: 100%; object-fit: cover;">
                            @else
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                    style="height: 300px;">
                                    <i class="fas fa-book fa-5x text-white"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <h2 class="mb-3">{{ $course->title }}</h2>
                            <div class="mb-3">
                                <form action="{{ route('admin.courses.update-status', $course->id) }}" method="POST"
                                    class="status-form d-inline-block">
                                    @csrf
                                    @php
                                        $statusClasses = [
                                            'published' => 'bg-success text-white',
                                            'pending' => 'bg-warning text-dark',
                                            'draft' => 'bg-secondary text-white',
                                            'archived' => 'bg-dark text-white',
                                        ];
                                        $currentStatusClass = $statusClasses[$course->status] ?? '';
                                    @endphp
                                    <select name="status"
                                        class="form-control form-control-sm status-select d-inline-block {{ $currentStatusClass }}"
                                        style="min-width: 120px;" data-course-id="{{ $course->id }}"
                                        data-current-status="{{ $course->status }}"
                                        onchange="updateStatusColor(this); this.form.submit();">
                                        <option value="draft" {{ $course->status === 'draft' ? 'selected' : '' }}>Draft
                                        </option>
                                        <option value="pending" {{ $course->status === 'pending' ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="published" {{ $course->status === 'published' ? 'selected' : '' }}>
                                            Published</option>
                                        <option value="archived" {{ $course->status === 'archived' ? 'selected' : '' }}>
                                            Archived</option>
                                    </select>
                                </form>
                                @if ($course->category)
                                    <span class="badge badge-info ml-2">{{ $course->category }}</span>
                                @endif
                                @if ($course->difficulty)
                                    <span class="badge badge-primary ml-2">{{ ucfirst($course->difficulty) }}</span>
                                @endif
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Instructor:</strong>
                                        @if ($course->instructor)
                                            {{ $course->instructor->name }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </p>
                                    <p class="mb-2"><strong>Pricing:</strong>
                                        @if ($course->pricing_model === 'free')
                                            <span class="badge badge-success">Free</span>
                                        @else
                                            @if ($course->sale_price)
                                                <strong>{{ currency_format($course->sale_price) }}</strong>
                                                <small
                                                    class="text-muted text-decoration-line-through ml-2">{{ currency_format($course->regular_price ?? 0) }}</small>
                                            @else
                                                <strong>{{ currency_format($course->regular_price ?? 0) }}</strong>
                                            @endif
                                        @endif
                                    </p>
                                    <p class="mb-2"><strong>Language:</strong> {{ $course->language ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Enrollments:</strong> {{ $course->enrollments->count() }}</p>
                                    <p class="mb-2"><strong>Lessons:</strong> {{ $lessonCount }}</p>
                                    <p class="mb-2"><strong>Quizzes:</strong> {{ $quizCount }}</p>
                                    <p class="mb-2"><strong>Assignments:</strong> {{ $assignmentCount }}</p>
                                    @if ($totalDuration > 0)
                                        <p class="mb-2"><strong>Duration:</strong> {{ round($totalDuration / 60, 1) }}
                                            hours</p>
                                    @endif
                                </div>
                            </div>
                            @if ($totalReviews > 0)
                                <div class="mb-3">
                                    <strong>Rating:</strong>
                                    <span class="text-warning">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star{{ $i <= round($averageRating) ? '' : '-o' }}"></i>
                                        @endfor
                                    </span>
                                    <span class="ml-2">{{ $averageRating }} ({{ $totalReviews }} reviews)</span>
                                </div>
                            @endif
                            @if ($course->deleted_date)
                                <div class="alert alert-warning mb-2">
                                    <i class="fas fa-archive"></i> This course was archived on
                                    {{ $course->deleted_date->format('M d, Y h:i A') }}
                                </div>
                            @endif
                            @if ($course->approval_reason)
                                <div class="alert alert-success mb-2">
                                    <strong><i class="fas fa-check-circle"></i> Approval Reason:</strong><br>
                                    {{ $course->approval_reason }}
                                </div>
                            @endif
                            @if ($course->rejection_reason)
                                <div class="alert alert-danger mb-0">
                                    <strong><i class="fas fa-times-circle"></i> Rejection Reason:</strong><br>
                                    {{ $course->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                @if ($course->status === 'pending')
                                    <button type="button" class="btn btn-success approve-course-btn"
                                        data-course-id="{{ $course->id }}" data-course-title="{{ $course->title }}">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-warning reject-course-btn"
                                        data-course-id="{{ $course->id }}" data-course-title="{{ $course->title }}">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                @endif
                                @if ($course->status !== 'archived')
                                    <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to archive this course?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="courseTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="description-tab" data-toggle="tab" href="#description"
                                role="tab">Description</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="curriculum-tab" data-toggle="tab" href="#curriculum"
                                role="tab">Curriculum</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="instructor-tab" data-toggle="tab" href="#instructor"
                                role="tab">Instructor</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="reviews-tab" data-toggle="tab" href="#reviews"
                                role="tab">Reviews ({{ $totalReviews }})</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="courseTabsContent">
                        <!-- Description Tab -->
                        <div class="tab-pane fade show active" id="description" role="tabpanel">
                            <div class="mt-4">
                                <h5>Description</h5>
                                <div class="mb-4">
                                    {!! $course->description ?? '<p class="text-muted">No description available.</p>' !!}
                                </div>

                                @if ($course->objectives)
                                    <h5 class="mt-4">What you'll learn in this course?</h5>
                                    <ul class="list-unstyled">
                                        @foreach (explode("\n", $course->objectives) as $objective)
                                            @if (trim($objective))
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                                    {{ trim($objective) }}
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($course->requirements)
                                    <h5 class="mt-4">Requirements</h5>
                                    <ul class="list-unstyled">
                                        @foreach (explode("\n", $course->requirements) as $requirement)
                                            @if (trim($requirement))
                                                <li class="mb-2">
                                                    <i class="fas fa-circle text-primary mr-2"
                                                        style="font-size: 8px;"></i>
                                                    {{ trim($requirement) }}
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($course->tags)
                                    <h5 class="mt-4">Tags</h5>
                                    <div>
                                        @foreach (explode(',', $course->tags) as $tag)
                                            @if (trim($tag))
                                                <span class="badge badge-secondary mr-2">{{ trim($tag) }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <p><strong>Visibility:</strong> {{ ucfirst($course->visibility) }}</p>
                                        <p><strong>Public Course:</strong> {{ $course->public_course ? 'Yes' : 'No' }}</p>
                                        @if ($course->max_students)
                                            <p><strong>Max Students:</strong> {{ $course->max_students }}</p>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Created:</strong> {{ $course->created_at->format('M d, Y h:i A') }}</p>
                                        <p><strong>Updated:</strong> {{ $course->updated_at->format('M d, Y h:i A') }}</p>
                                        @if ($course->schedule_enabled && $course->schedule_date)
                                            <p><strong>Scheduled Date:</strong>
                                                {{ $course->schedule_date->format('M d, Y h:i A') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Curriculum Tab -->
                        <div class="tab-pane fade" id="curriculum" role="tabpanel">
                            <div class="mt-4">
                                <h5>Course Curriculum</h5>
                                @if ($course->topics->count() > 0)
                                    <div class="accordion" id="curriculumAccordion">
                                        @foreach ($course->topics as $index => $topic)
                                            <div class="card mb-2">
                                                <div class="card-header" id="heading{{ $topic->id }}">
                                                    <h6 class="mb-0">
                                                        <button class="btn btn-link" type="button"
                                                            data-toggle="collapse"
                                                            data-target="#collapse{{ $topic->id }}"
                                                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                                                            <i
                                                                class="fas fa-chevron-{{ $index === 0 ? 'down' : 'right' }} mr-2"></i>
                                                            {{ $topic->title }}
                                                            <span class="badge badge-info ml-2">
                                                                {{ $topic->lessons->count() }} Lessons,
                                                                {{ $topic->quizzes->count() }} Quizzes,
                                                                {{ $topic->assignments->count() }} Assignments
                                                            </span>
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div id="collapse{{ $topic->id }}"
                                                    class="collapse {{ $index === 0 ? 'show' : '' }}"
                                                    aria-labelledby="heading{{ $topic->id }}">
                                                    <div class="card-body">
                                                        @if ($topic->description)
                                                            <p class="mb-3">{{ $topic->description }}</p>
                                                        @endif
                                                        <ul class="list-unstyled">
                                                            @foreach ($topic->lessons as $lesson)
                                                                <li class="mb-2">
                                                                    <i class="fas fa-play-circle text-primary mr-2"></i>
                                                                    <strong>Lesson:</strong> {{ $lesson->title }}
                                                                    @if ($lesson->duration)
                                                                        <span
                                                                            class="text-muted ml-2">({{ $lesson->duration }}
                                                                            min)</span>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                            @foreach ($topic->quizzes as $quiz)
                                                                <li class="mb-2">
                                                                    <i
                                                                        class="fas fa-question-circle text-warning mr-2"></i>
                                                                    <strong>Quiz:</strong> {{ $quiz->title }}
                                                                    @if ($quiz->time_limit)
                                                                        <span
                                                                            class="text-muted ml-2">({{ $quiz->time_limit }}
                                                                            min)</span>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                            @foreach ($topic->assignments as $assignment)
                                                                <li class="mb-2">
                                                                    <i class="fas fa-tasks text-success mr-2"></i>
                                                                    <strong>Assignment:</strong> {{ $assignment->title }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <p>No curriculum available for this course yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Instructor Tab -->
                        <div class="tab-pane fade" id="instructor" role="tabpanel">
                            <div class="mt-4">
                                <h5>Instructor Information</h5>
                                @if ($course->instructor)
                                    <div class="row">
                                        <div class="col-md-3">
                                            @if ($course->instructor->profile_photo)
                                                <img src="{{ asset('storage/' . $course->instructor->profile_photo) }}"
                                                    alt="{{ $course->instructor->name }}"
                                                    class="img-fluid rounded-circle w-75">
                                            @else
                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                                    style="height: 200px;">
                                                    <i class="fas fa-user fa-3x text-white"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-9">
                                            <h4>{{ $course->instructor->name }}</h4>
                                            @if ($course->instructor->specialization)
                                                <p class="text-muted">{{ $course->instructor->specialization }}</p>
                                            @endif
                                            @if ($course->instructor->bio)
                                                <p>{{ $course->instructor->bio }}</p>
                                            @endif
                                            <div class="mt-3">
                                                <p><strong>Email:</strong> {{ $course->instructor->email }}</p>
                                                @if ($course->instructor->phone)
                                                    <p><strong>Phone:</strong> {{ $course->instructor->phone }}</p>
                                                @endif
                                                @if ($course->instructor->years_experience)
                                                    <p><strong>Experience:</strong>
                                                        {{ $course->instructor->years_experience }} years</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <p>Instructor information not available.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <div class="mt-4">
                                <h5>Course Reviews</h5>
                                @if ($totalReviews > 0)
                                    <div class="mb-4">
                                        <div class="row">
                                            <div class="col-md-4 text-center">
                                                <h2 class="mb-0">{{ $averageRating }}</h2>
                                                <div class="text-warning mb-2">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i
                                                            class="fas fa-star{{ $i <= round($averageRating) ? '' : '-o' }}"></i>
                                                    @endfor
                                                </div>
                                                <p class="text-muted">{{ $totalReviews }} reviews</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="reviews-list">
                                        @foreach ($course->reviews as $review)
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                @if ($review->user->profile_photo)
                                                                    <img src="{{ asset('storage/' . $review->user->profile_photo) }}"
                                                                        alt="{{ $review->user->name }}"
                                                                        class="rounded-circle mr-2"
                                                                        style="width: 40px; height: 40px; object-fit: cover;">
                                                                @else
                                                                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mr-2"
                                                                        style="width: 40px; height: 40px;">
                                                                        <i class="fas fa-user text-white"></i>
                                                                    </div>
                                                                @endif
                                                                {{ $review->user->name }}
                                                            </h6>
                                                            <small
                                                                class="text-muted">{{ $review->created_at->format('M d, Y') }}</small>
                                                        </div>
                                                        <div class="text-warning">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <i
                                                                    class="fas fa-star{{ $i <= $review->rating ? '' : '-o' }}"></i>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                    @if ($review->comment)
                                                        <p class="mb-0">{{ $review->comment }}</p>
                                                    @endif

                                                    @if ($review->replies->count() > 0)
                                                        <div class="mt-3 pl-4 border-left border-primary">
                                                            <h6 class="mb-2">Instructor Replies:</h6>
                                                            @foreach ($review->replies as $reply)
                                                                <div class="mb-2">
                                                                    <strong>{{ $reply->user->name }}:</strong>
                                                                    <span
                                                                        class="text-muted ml-2">{{ $reply->reply }}</span>
                                                                    <small
                                                                        class="text-muted d-block">{{ $reply->created_at->format('M d, Y') }}</small>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <p>No reviews yet for this course.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function updateStatusColor(selectElement) {
                const $select = $(selectElement);
                const status = $select.val();

                $select.removeClass('bg-success bg-warning bg-secondary bg-dark text-white text-dark');

                switch (status) {
                    case 'published':
                        $select.addClass('bg-success text-white');
                        break;
                    case 'pending':
                        $select.addClass('bg-warning text-dark');
                        break;
                    case 'draft':
                        $select.addClass('bg-secondary text-white');
                        break;
                    case 'archived':
                        $select.addClass('bg-dark text-white');
                        break;
                }
            }

            $(document).ready(function() {
                $('.status-select').each(function() {
                    updateStatusColor(this);
                });

                $(document).on('click', '.approve-course-btn', function() {
                    const courseId = $(this).data('course-id');
                    const courseTitle = $(this).data('course-title');

                    Swal.fire({
                        title: 'Approve Course',
                        html: `
                <p>Are you sure you want to approve this course?</p>
                <p><strong>${courseTitle}</strong></p>
                <textarea id="approve-reason" class="swal2-textarea" placeholder="Enter approval reason (optional)" style="width: -webkit-fill-available; min-height: 100px; margin-top: 10px;"></textarea>
            `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-check"></i> Approve',
                        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                        preConfirm: () => {
                            return {
                                reason: document.getElementById('approve-reason').value
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const reason = result.value.reason;

                            const form = $('<form>', {
                                'method': 'POST',
                                'action': '{{ route('admin.courses.approve', ':id') }}'
                                    .replace(':id', courseId)
                            });

                            form.append($('<input>', {
                                'type': 'hidden',
                                'name': '_token',
                                'value': '{{ csrf_token() }}'
                            }));

                            if (reason) {
                                form.append($('<input>', {
                                    'type': 'hidden',
                                    'name': 'reason',
                                    'value': reason
                                }));
                            }

                            $('body').append(form);
                            form.submit();
                        }
                    });
                });

                $(document).on('click', '.reject-course-btn', function() {
                    const courseId = $(this).data('course-id');
                    const courseTitle = $(this).data('course-title');

                    Swal.fire({
                        title: 'Reject Course',
                        html: `
                <p>Are you sure you want to reject this course?</p>
                <p><strong>${courseTitle}</strong></p>
                <textarea id="reject-reason" class="swal2-textarea" placeholder="Enter rejection reason (optional)" style="width: -webkit-fill-available; min-height: 100px; margin-top: 10px;"></textarea>
            `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-times"></i> Reject',
                        cancelButtonText: '<i class="fas fa-arrow-left"></i> Cancel',
                        preConfirm: () => {
                            return {
                                reason: document.getElementById('reject-reason').value
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const reason = result.value.reason;

                            const form = $('<form>', {
                                'method': 'POST',
                                'action': '{{ route('admin.courses.reject', ':id') }}'.replace(
                                    ':id', courseId)
                            });

                            form.append($('<input>', {
                                'type': 'hidden',
                                'name': '_token',
                                'value': '{{ csrf_token() }}'
                            }));

                            if (reason) {
                                form.append($('<input>', {
                                    'type': 'hidden',
                                    'name': 'reason',
                                    'value': reason
                                }));
                            }

                            $('body').append(form);
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
