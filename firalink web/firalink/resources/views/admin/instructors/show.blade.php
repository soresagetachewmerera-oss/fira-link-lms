@extends('layouts.admin')

@section('title', 'Instructor Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.instructors.index') }}">Instructors</a></div>
        <div class="breadcrumb-item">Instructor Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Instructor Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.instructors.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Instructors
                        </a>
                        <a href="{{ route('admin.instructors.edit', $instructor->id) }}" class="btn btn-icon btn-warning">
                            <i class="fas fa-edit"></i> Edit Instructor
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Instructor Header -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            @if ($instructor->profile_photo)
                                <img src="{{ asset('storage/' . $instructor->profile_photo) }}"
                                    alt="{{ $instructor->name }}" class="img-fluid rounded-circle profile-photo-xl">
                            @else
                                <div
                                    class="bg-secondary rounded-circle d-flex align-items-center justify-content-center profile-placeholder-xl mx-auto">
                                    <i class="fas fa-user fa-5x text-white"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <h2 class="mb-3">{{ $instructor->name }}</h2>
                            <div class="mb-3">
                                @if ($instructor->is_approved)
                                    <span class="badge badge-success">Approved</span>
                                @elseif($instructor->rejection_reason)
                                    <span class="badge badge-danger">Rejected</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                                @if ($instructor->specialization)
                                    <span class="badge badge-info ml-2">{{ $instructor->specialization }}</span>
                                @endif
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Email:</strong> {{ $instructor->email }}</p>
                                    @if ($instructor->phone)
                                        <p class="mb-2"><strong>Phone:</strong> {{ $instructor->phone }}</p>
                                    @endif
                                    @if ($instructor->years_experience)
                                        <p class="mb-2"><strong>Experience:</strong> {{ $instructor->years_experience }}
                                            years</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Total Courses:</strong> {{ $totalCourses }}</p>
                                    <p class="mb-2"><strong>Total Enrollments:</strong> {{ $totalEnrollments }}</p>
                                    <p class="mb-2"><strong>Total Revenue:</strong> {{ currency_format($totalRevenue) }}
                                    </p>
                                    <p class="mb-2"><strong>Published Courses:</strong> {{ $publishedCourses }}</p>
                                    <p class="mb-2"><strong>Pending Courses:</strong> {{ $pendingCourses }}</p>
                                </div>
                            </div>
                            @if ($instructor->approval_reason)
                                <div class="alert alert-success mb-2">
                                    <strong><i class="fas fa-check-circle"></i> Approval Reason:</strong><br>
                                    {{ $instructor->approval_reason }}
                                </div>
                            @endif
                            @if ($instructor->rejection_reason)
                                <div class="alert alert-danger mb-0">
                                    <strong><i class="fas fa-times-circle"></i> Rejection Reason:</strong><br>
                                    {{ $instructor->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                @if (!$instructor->is_approved && !$instructor->rejection_reason)
                                    <button type="button" class="btn btn-success approve-instructor-btn"
                                        data-instructor-id="{{ $instructor->id }}"
                                        data-instructor-name="{{ $instructor->name }}">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-warning reject-instructor-btn"
                                        data-instructor-id="{{ $instructor->id }}"
                                        data-instructor-name="{{ $instructor->name }}">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                @endif
                                @if ($instructor->is_approved)
                                    <form action="{{ route('admin.impersonate.instructor', $instructor->id) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-secret"></i> Login as Instructor
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.instructors.edit', $instructor->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit Details
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="instructorTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile"
                                role="tab">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="courses-tab" data-toggle="tab" href="#courses" role="tab">Courses
                                ({{ $totalCourses }})</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="instructorTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="mt-4">
                                <h5>Profile Information</h5>

                                @if ($instructor->bio)
                                    <div class="mb-4">
                                        <h6>Bio</h6>
                                        <p>{{ $instructor->bio }}</p>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Education & Experience</h6>
                                        <ul class="list-unstyled">
                                            @if ($instructor->highest_degree)
                                                <li class="mb-2">
                                                    <strong>Highest Degree:</strong> {{ $instructor->highest_degree }}
                                                </li>
                                            @endif
                                            @if ($instructor->field_of_study)
                                                <li class="mb-2">
                                                    <strong>Field of Study:</strong> {{ $instructor->field_of_study }}
                                                </li>
                                            @endif
                                            @if ($instructor->years_experience)
                                                <li class="mb-2">
                                                    <strong>Years of Experience:</strong>
                                                    {{ $instructor->years_experience }}
                                                </li>
                                            @endif
                                            @if ($instructor->previous_experience)
                                                <li class="mb-2">
                                                    <strong>Previous Experience:</strong><br>
                                                    <p class="text-muted">{{ $instructor->previous_experience }}</p>
                                                </li>
                                            @endif
                                            @if ($instructor->certifications)
                                                <li class="mb-2">
                                                    <strong>Certifications:</strong><br>
                                                    <p class="text-muted">{{ $instructor->certifications }}</p>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Contact & Social Media</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2">
                                                <strong>Email:</strong> {{ $instructor->email }}
                                            </li>
                                            @if ($instructor->phone)
                                                <li class="mb-2">
                                                    <strong>Phone:</strong> {{ $instructor->phone }}
                                                </li>
                                            @endif
                                            @if ($instructor->linkedin)
                                                <li class="mb-2">
                                                    <strong>LinkedIn:</strong>
                                                    <a href="{{ $instructor->linkedin }}"
                                                        target="_blank">{{ $instructor->linkedin }}</a>
                                                </li>
                                            @endif
                                            @if ($instructor->twitter)
                                                <li class="mb-2">
                                                    <strong>Twitter:</strong>
                                                    <a href="{{ $instructor->twitter }}"
                                                        target="_blank">{{ $instructor->twitter }}</a>
                                                </li>
                                            @endif
                                            @if ($instructor->facebook)
                                                <li class="mb-2">
                                                    <strong>Facebook:</strong>
                                                    <a href="{{ $instructor->facebook }}"
                                                        target="_blank">{{ $instructor->facebook }}</a>
                                                </li>
                                            @endif
                                            @if ($instructor->youtube)
                                                <li class="mb-2">
                                                    <strong>YouTube:</strong>
                                                    <a href="{{ $instructor->youtube }}"
                                                        target="_blank">{{ $instructor->youtube }}</a>
                                                </li>
                                            @endif
                                        </ul>

                                        @if ($instructor->resume)
                                            <div class="mt-3">
                                                <a href="{{ asset('storage/' . $instructor->resume) }}" target="_blank"
                                                    class="btn btn-primary">
                                                    <i class="fas fa-download"></i> Download Resume
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <p><strong>Member Since:</strong> {{ $instructor->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Last Updated:</strong>
                                            {{ $instructor->updated_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Courses Tab -->
                        <div class="tab-pane fade" id="courses" role="tabpanel">
                            <div class="mt-4">
                                <h5>Instructor Courses</h5>
                                @if ($instructor->courses->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Status</th>
                                                    <th>Price</th>
                                                    <th>Enrollments</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($instructor->courses as $course)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                @if ($course->featured_image)
                                                                    <img src="{{ asset('storage/' . $course->featured_image) }}"
                                                                        alt="{{ $course->title }}"
                                                                        class="mr-3 rounded course-thumb-sm">
                                                                @else
                                                                    <div
                                                                        class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center course-thumb-placeholder">
                                                                        <i class="fas fa-book text-white"></i>
                                                                    </div>
                                                                @endif
                                                                <div>
                                                                    <h6 class="mb-0">
                                                                        {{ Str::limit($course->title, 50) }}</h6>
                                                                    <small
                                                                        class="text-muted">{{ $course->category ?? 'Uncategorized' }}</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if ($course->status === 'published')
                                                                <span class="badge badge-success">Published</span>
                                                            @elseif($course->status === 'pending')
                                                                <span class="badge badge-warning">Pending</span>
                                                            @elseif($course->status === 'draft')
                                                                <span class="badge badge-secondary">Draft</span>
                                                            @elseif($course->status === 'archived')
                                                                <span class="badge badge-dark">Archived</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($course->pricing_model === 'free')
                                                                <span class="badge badge-success">Free</span>
                                                            @else
                                                                @if ($course->sale_price)
                                                                    <strong>{{ currency_format($course->sale_price) }}</strong>
                                                                    <br>
                                                                    <small
                                                                        class="text-muted text-decoration-line-through">{{ currency_format($course->regular_price ?? 0) }}</small>
                                                                @else
                                                                    <strong>{{ currency_format($course->regular_price ?? 0) }}</strong>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge badge-info">{{ $course->enrollments->count() }}</span>
                                                        </td>
                                                        <td>
                                                            <small>{{ $course->created_at->format('M d, Y') }}</small>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('admin.courses.show', $course->id) }}"
                                                                class="btn btn-sm btn-info" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <p>This instructor has not created any courses yet.</p>
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
            $(document).ready(function() {
                $(document).on('click', '.approve-instructor-btn', function() {
                    const instructorId = $(this).data('instructor-id');
                    const instructorName = $(this).data('instructor-name');

                    Swal.fire({
                        title: 'Approve Instructor',
                        html: `
                <p>Are you sure you want to approve this instructor?</p>
                <p><strong>${instructorName}</strong></p>
                <textarea id="approve-reason" class="swal2-textarea swal-textarea" placeholder="Enter approval reason (optional)"></textarea>
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
                                'action': '{{ route('admin.instructors.approve', ':id') }}'
                                    .replace(':id', instructorId)
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

                $(document).on('click', '.reject-instructor-btn', function() {
                    const instructorId = $(this).data('instructor-id');
                    const instructorName = $(this).data('instructor-name');

                    Swal.fire({
                        title: 'Reject Instructor',
                        html: `
                <p>Are you sure you want to reject this instructor application?</p>
                <p><strong>${instructorName}</strong></p>
                <textarea id="reject-reason" class="swal2-textarea swal-textarea" placeholder="Enter rejection reason (optional)"></textarea>
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
                                'action': '{{ route('admin.instructors.reject', ':id') }}'
                                    .replace(':id', instructorId)
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
