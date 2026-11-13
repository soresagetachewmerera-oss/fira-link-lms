@extends('layouts.admin')

@section('title', 'All Courses')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Courses</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <form method="GET" action="{{ route('admin.courses.index') }}">
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}">
                                @endif
                                @if (request('visibility'))
                                    <input type="hidden" name="visibility" value="{{ request('visibility') }}">
                                @endif
                                @if (request('pricing_model'))
                                    <input type="hidden" name="pricing_model" value="{{ request('pricing_model') }}">
                                @endif
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search courses..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <form method="GET" action="{{ route('admin.courses.index') }}">
                                @if (request('search'))
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                @endif
                                @if (request('visibility'))
                                    <input type="hidden" name="visibility" value="{{ request('visibility') }}">
                                @endif
                                @if (request('pricing_model'))
                                    <input type="hidden" name="pricing_model" value="{{ request('pricing_model') }}">
                                @endif
                                <select class="form-control" name="status" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>
                                        Published</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft
                                    </option>
                                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>
                                        Archived</option>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <form method="GET" action="{{ route('admin.courses.index') }}">
                                @if (request('search'))
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                @endif
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}">
                                @endif
                                @if (request('pricing_model'))
                                    <input type="hidden" name="pricing_model" value="{{ request('pricing_model') }}">
                                @endif
                                <select class="form-control" name="visibility" onchange="this.form.submit()">
                                    <option value="">All Visibility</option>
                                    <option value="public" {{ request('visibility') == 'public' ? 'selected' : '' }}>Public
                                    </option>
                                    <option value="private" {{ request('visibility') == 'private' ? 'selected' : '' }}>
                                        Private</option>
                                    <option value="draft" {{ request('visibility') == 'draft' ? 'selected' : '' }}>Draft
                                    </option>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <form method="GET" action="{{ route('admin.courses.index') }}">
                                @if (request('search'))
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                @endif
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}">
                                @endif
                                @if (request('visibility'))
                                    <input type="hidden" name="visibility" value="{{ request('visibility') }}">
                                @endif
                                <select class="form-control" name="pricing_model" onchange="this.form.submit()">
                                    <option value="">All Pricing</option>
                                    <option value="free" {{ request('pricing_model') == 'free' ? 'selected' : '' }}>Free
                                    </option>
                                    <option value="paid" {{ request('pricing_model') == 'paid' ? 'selected' : '' }}>Paid
                                    </option>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-3 text-right">
                            <span class="badge badge-info">Total: {{ $courses->total() }} courses</span>
                            @if ($pendingCount > 0)
                                <a href="{{ route('admin.courses.index') }}?status=pending" class="badge badge-warning">
                                    Pending: {{ $pendingCount }}
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="courses-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Instructor</th>
                                    <th>Price</th>
                                    <th>Enrollments</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($courses as $course)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($course->featured_image)
                                                    <img src="{{ asset('storage/' . $course->featured_image) }}"
                                                        alt="{{ $course->title }}" class="mr-3 rounded p-1"
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                @else
                                                    <div class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center"
                                                        style="width: 60px; height: 60px;">
                                                        <i class="fas fa-book text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ Str::limit($course->title, 50) }}</h6>
                                                    <small
                                                        class="text-muted">{{ $course->category ?? 'Uncategorized' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($course->instructor)
                                                <div>
                                                    <strong>{{ $course->instructor->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $course->instructor->email }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($course->pricing_model === 'free')
                                                <span class="badge badge-success">Free</span>
                                            @else
                                                @if ($course->sale_price)
                                                    <div>
                                                        <strong>{{ currency_format($course->sale_price) }}</strong>
                                                        <br>
                                                        <small
                                                            class="text-muted text-decoration-line-through">{{ currency_format($course->regular_price ?? 0) }}</small>
                                                    </div>
                                                @else
                                                    <strong>{{ currency_format($course->regular_price ?? 0) }}</strong>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $course->enrollments->count() }}</span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.courses.update-status', $course->id) }}"
                                                method="POST" class="status-form" style="display: inline-block;">
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
                                                    class="form-control form-control-sm status-select {{ $currentStatusClass }}"
                                                    style="min-width: 120px;" data-course-id="{{ $course->id }}"
                                                    data-current-status="{{ $course->status }}"
                                                    onchange="updateStatusColor(this); this.form.submit();">
                                                    <option value="draft"
                                                        {{ $course->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                                    <option value="pending"
                                                        {{ $course->status === 'pending' ? 'selected' : '' }}>Pending
                                                    </option>
                                                    <option value="published"
                                                        {{ $course->status === 'published' ? 'selected' : '' }}>Published
                                                    </option>
                                                    <option value="archived"
                                                        {{ $course->status === 'archived' ? 'selected' : '' }}>Archived
                                                    </option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <small>{{ $course->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if ($course->status === 'pending')
                                                    <button type="button"
                                                        class="btn btn-sm btn-success approve-course-btn"
                                                        data-course-id="{{ $course->id }}"
                                                        data-course-title="{{ $course->title }}" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-warning reject-course-btn"
                                                        data-course-id="{{ $course->id }}"
                                                        data-course-title="{{ $course->title }}" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('admin.courses.show', $course->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if ($course->status !== 'archived')
                                                    <form action="{{ route('admin.courses.destroy', $course->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to archive this course?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                            title="Archive">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-dark full-delete-btn"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-title="{{ $course->title }}" title="Delete Permanently">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No courses found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">
                                Showing {{ $courses->firstItem() ?? 0 }} to {{ $courses->lastItem() ?? 0 }} of
                                {{ $courses->total() }} courses
                            </p>
                        </div>
                        <div>
                            {{ $courses->links('vendor.pagination.stisla-default') }}
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

                $(document).on('click', '.full-delete-btn', function() {
                    const courseId = $(this).data('course-id');
                    const courseTitle = $(this).data('course-title');

                    // Step 1: Warning Confirmation
                    Swal.fire({
                        title: 'PERMANENT DELETION',
                        html: `
                <div class="alert alert-danger text-left">
                    <p><strong>WARNING:</strong> This action is IRREVERSIBLE.</p>
                    <p>Deleting <strong>${courseTitle}</strong> will also remove:</p>
                    <ul class="mb-0">
                        <li>All Student Enrollments</li>
                        <li>Payment Logs & Transaction History</li>
                        <li>Course Media (Videos, Images, Attachments)</li>
                        <li>Lessons, Quizzes, and Assignments</li>
                        <li>Reviews and Ratings</li>
                    </ul>
                </div>
                <p>Are you absolutely sure you want to proceed?</p>
            `,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, I understand',
                        cancelButtonText: 'No, Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Step 2: Verification Input
                            Swal.fire({
                                title: 'Verification Required',
                                text: 'Please type "DELETE" to confirm permanent deletion of this course.',
                                input: 'text',
                                inputAttributes: {
                                    autocapitalize: 'off'
                                },
                                showCancelButton: true,
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'Proceed to final step',
                                showLoaderOnConfirm: true,
                                preConfirm: (value) => {
                                    if (value !== 'DELETE') {
                                        Swal.showValidationMessage('You must type "DELETE" to confirm.');
                                    }
                                    return value;
                                },
                                allowOutsideClick: () => !Swal.isLoading()
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Step 3: Final Confirmation
                                    Swal.fire({
                                        title: 'FINAL WARNING',
                                        text: 'This is your last chance. Are you 100% certain? All data will be wiped immediately.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#dc3545',
                                        confirmButtonText: 'DELETE EVERYTHING',
                                        cancelButtonText: 'Stop, I changed my mind'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            const form = $('<form>', {
                                                'method': 'POST',
                                                'action': '{{ route('admin.courses.full-delete', ':id') }}'
                                                    .replace(':id', courseId)
                                            });

                                            form.append($('<input>', {
                                                'type': 'hidden',
                                                'name': '_token',
                                                'value': '{{ csrf_token() }}'
                                            }));

                                            form.append($('<input>', {
                                                'type': 'hidden',
                                                'name': '_method',
                                                'value': 'DELETE'
                                            }));

                                            $('body').append(form);
                                            form.submit();
                                        }
                                    });
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
