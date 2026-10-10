@extends('layouts.admin')

@section('title', 'Account Deletion Requests')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item active">Account Deletion Requests</div>
@endsection

@section('main-content')
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Pending</h4>
                        </div>
                        <div class="card-body">
                            {{ $statusCounts['pending'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Approved</h4>
                        </div>
                        <div class="card-body">
                            {{ $statusCounts['approved'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Rejected</h4>
                        </div>
                        <div class="card-body">
                            {{ $statusCounts['rejected'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills mb-3">
                    @foreach ($navigationTabs as $tab)
                        <li class="nav-item">
                            <a class="nav-link {{ $status === $tab['key'] ? 'active' : '' }}" href="{{ $tab['url'] }}">
                                {{ $tab['label'] }}
                                <span class="{{ $tab['badge_class'] }} ml-2">{{ $tab['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="card">
                    <div class="card-header">
                        <h4>Filters</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" method="GET" action="{{ route('admin.account-deletions.index') }}">
                            <div class="col-md-4">
                                <label for="status-filter" class="form-label">Status</label>
                                <select name="status" id="status-filter" class="form-control selectric">
                                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved
                                    </option>
                                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="role-filter" class="form-label">Role</label>
                                <select name="role" id="role-filter" class="form-control selectric">
                                    <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="student" {{ $role === 'student' ? 'selected' : '' }}>Students
                                        ({{ $roleCounts['student'] ?? 0 }})</option>
                                    <option value="instructor" {{ $role === 'instructor' ? 'selected' : '' }}>Instructors
                                        ({{ $roleCounts['instructor'] ?? 0 }})</option>
                                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admins
                                        ({{ $roleCounts['admin'] ?? 0 }})</option>
                                </select>
                            </div>
                            <div class="col-md-4 align-self-end">
                                <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter mr-2"></i>Apply
                                    Filters</button>
                                <a href="{{ route('admin.account-deletions.index') }}" class="btn btn-light">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Account Deletion Requests</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Requested At</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                        <th>Notes</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requests as $requestItem)
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold">{{ $requestItem->user_name }}</div>
                                                <div class="text-small text-muted">{{ $requestItem->user_email }}</div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge badge-secondary text-uppercase">{{ $requestItem->role }}</span>
                                            </td>
                                            <td>
                                                <div>{{ $requestItem->created_at->format('M d, Y') }}</div>
                                                <div class="text-small text-muted">
                                                    {{ $requestItem->created_at->format('h:i A') }}</div>
                                            </td>
                                            <td>
                                                <span
                                                    class="{{ $statusBadgeClasses[$requestItem->status] ?? 'badge badge-light' }} text-uppercase">
                                                    {{ $requestItem->status }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($requestItem->processed_by)
                                                    <div class="font-weight-bold">
                                                        {{ optional($requestItem->processedBy)->name ?? 'User Deleted' }}
                                                    </div>
                                                    <div class="text-small text-muted">
                                                        {{ optional($requestItem->processed_at)->format('M d, Y h:i A') }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($requestItem->note)
                                                    <span class="text-break">{{ $requestItem->note }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if ($requestItem->status === 'pending')
                                                    <button type="button" class="btn btn-success btn-sm mr-1"
                                                        onclick="handleApprove({{ $requestItem->id }})">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="handleReject({{ $requestItem->id }})">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                @else
                                                    <span class="text-muted">Processed</span>
                                                @endif

                                                <form id="approve-form-{{ $requestItem->id }}" method="POST"
                                                    action="{{ route('admin.account-deletions.approve', $requestItem) }}"
                                                    class="d-none">
                                                    @csrf
                                                    @method('PUT')
                                                </form>
                                                <form id="reject-form-{{ $requestItem->id }}" method="POST"
                                                    action="{{ route('admin.account-deletions.reject', $requestItem) }}"
                                                    class="d-none">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="note" value="">
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <h6 class="mb-1">No account deletion requests found.</h6>
                                                <p class="text-muted mb-0">Once users submit deletion requests they will
                                                    appear here for review.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        {{ $requests->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function handleApprove(id) {
            const form = document.getElementById(`approve-form-${id}`);

            Swal.fire({
                title: 'Approve deletion?',
                text: 'This will permanently remove the user and all related data.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#47c363',
                cancelButtonColor: '#fc544b',
                confirmButtonText: 'Yes, approve'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function handleReject(id) {
            const form = document.getElementById(`reject-form-${id}`);
            const noteInput = form.querySelector('input[name="note"]');

            Swal.fire({
                title: 'Reject deletion request',
                input: 'textarea',
                inputPlaceholder: 'Provide a reason for rejecting this request...',
                inputAttributes: {
                    'aria-label': 'Reason for rejection',
                    'rows': 3
                },
                inputValidator: (value) => {
                    if (!value) {
                        return 'A reason is required to reject this request.';
                    }
                    if (value.length > 500) {
                        return 'Reason must be 500 characters or fewer.';
                    }
                },
                showCancelButton: true,
                confirmButtonColor: '#fc544b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject Request'
            }).then((result) => {
                if (result.isConfirmed) {
                    noteInput.value = result.value;
                    form.submit();
                }
            });
        }
    </script>
@endpush
