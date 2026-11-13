@extends('layouts.admin')

@section('title', 'Pending Instructors')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.instructors.index') }}">Instructors</a></div>
        <div class="breadcrumb-item">Pending</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('admin.instructors.pending') }}">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search pending instructors..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 text-right">
                            <span class="badge badge-warning">Total: {{ $pendingInstructors->total() }} pending</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="pending-instructors-table">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Specialization</th>
                                    <th>Experience</th>
                                    <th>Courses</th>
                                    <th>Applied</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingInstructors as $instructor)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($instructor->profile_photo)
                                                    <img src="{{ asset('storage/' . $instructor->profile_photo) }}"
                                                        alt="{{ $instructor->name }}" class="mr-3 rounded-circle"
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                        style="width: 50px; height: 50px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $instructor->name }}</h6>
                                                    <small class="text-muted">{{ $instructor->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $instructor->specialization ?? 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $instructor->years_experience ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $instructor->courses_count }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $instructor->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-success approve-instructor-btn"
                                                    data-instructor-id="{{ $instructor->id }}"
                                                    data-instructor-name="{{ $instructor->name }}" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning reject-instructor-btn"
                                                    data-instructor-id="{{ $instructor->id }}"
                                                    data-instructor-name="{{ $instructor->name }}" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <a href="{{ route('admin.instructors.show', $instructor->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No pending instructor applications at this time.</p>
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
                                Showing {{ $pendingInstructors->firstItem() ?? 0 }} to
                                {{ $pendingInstructors->lastItem() ?? 0 }} of {{ $pendingInstructors->total() }} pending
                                instructors
                            </p>
                        </div>
                        <div>
                            {{ $pendingInstructors->links('vendor.pagination.stisla-default') }}
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
                <textarea id="approve-reason" class="swal2-textarea" placeholder="Enter approval reason (optional)" style="width: 100%; min-height: 100px; margin-top: 10px;"></textarea>
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
                <textarea id="reject-reason" class="swal2-textarea" placeholder="Enter rejection reason (optional)" style="width: 100%; min-height: 100px; margin-top: 10px;"></textarea>
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
