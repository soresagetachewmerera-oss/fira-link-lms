@extends('layouts.admin')

@section('title', 'Instructor Withdrawals')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Payout Requests</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="section-title mt-0">Payout Overview</div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Requests</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['pending_count'] }} <span
                            class="d-block small text-muted">{{ currency_format($stats['pending_total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Processing</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['processing_count'] }} <span
                            class="d-block small text-muted">{{ currency_format($stats['processing_total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Completed</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['completed_count'] }} <span
                            class="d-block small text-muted">{{ currency_format($stats['completed_total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Rejected</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['rejected_count'] }} <span
                            class="d-block small text-muted">{{ currency_format($stats['rejected_total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="mb-4">
                        <div class="form-row align-items-end">
                            <div class="col-md-4">
                                <label for="search">Search</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="{{ $search }}" placeholder="Search reference, instructor, email">
                            </div>
                            <div class="col-md-3">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">All statuses</option>
                                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing
                                    </option>
                                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed
                                    </option>
                                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="method">Method</label>
                                <select name="method" id="method" class="form-control">
                                    <option value="">All methods</option>
                                    @foreach ($methods as $methodOption)
                                        <option value="{{ $methodOption }}"
                                            {{ $method === $methodOption ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $methodOption)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 text-right">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>
                                    Filter</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Instructor</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                    <th>Processed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($withdrawals as $withdrawal)
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold">{{ $withdrawal->reference }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div>{{ $withdrawal->instructor->name ?? 'Unknown' }}</div>
                                                    <small
                                                        class="text-muted">{{ $withdrawal->instructor->email ?? '—' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ currency_format($withdrawal->amount) }}</strong>
                                        </td>
                                        <td>
                                            <div>{{ ucfirst(str_replace('_', ' ', $withdrawal->method)) }}</div>
                                            <small class="text-muted">{{ $withdrawal->method_details ?? '—' }}</small>
                                        </td>
                                        <td>{{ optional($withdrawal->requested_at)->format('M d, Y H:i') ?? $withdrawal->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'completed' => 'success',
                                                    'rejected' => 'danger',
                                                ];
                                            @endphp
                                            <span
                                                class="badge badge-{{ $statusColors[$withdrawal->status] ?? 'secondary' }}">{{ ucfirst($withdrawal->status) }}</span>
                                        </td>
                                        <td>
                                            {{ optional($withdrawal->processed_at)->format('M d, Y H:i') ?? '—' }}
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.withdrawals.show', $withdrawal) }}"
                                                class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if (in_array($withdrawal->status, [
                                                    \App\Models\InstructorWithdrawal::STATUS_PENDING,
                                                    \App\Models\InstructorWithdrawal::STATUS_PROCESSING,
                                                ]))
                                                <form action="{{ route('admin.withdrawals.status', $withdrawal) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="processing">
                                                    <button type="submit" class="btn btn-sm btn-warning"
                                                        title="Mark Processing"
                                                        onclick="return confirm('Mark this payout as processing?')">
                                                        <i class="fas fa-spinner"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.withdrawals.status', $withdrawal) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="btn btn-sm btn-success"
                                                        title="Mark Completed"
                                                        onclick="return confirm('Approve and mark this payout as completed?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.withdrawals.status', $withdrawal) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Reject"
                                                        onclick="return confirm('Reject this payout request? Amount will be returned to the instructor balance.')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-hand-holding-usd"></i>
                                                </div>
                                                <h5>No withdrawal requests found</h5>
                                                <p class="text-muted mb-0">Once instructors submit payout requests they
                                                    will appear here for review.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pagination['lastPage'] > 1)
                        <div class="table-pagination">
                            <div class="pagination-info">
                                Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of
                                {{ $pagination['total'] }} withdrawals
                            </div>
                            <div class="pagination-links">
                                @if ($pagination['currentPage'] > 1)
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['currentPage'] - 1]) }}"
                                        class="pagination-btn">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </span>
                                @endif

                                <div class="pagination-numbers">
                                    @for ($i = max(1, $pagination['currentPage'] - 2); $i <= min($pagination['lastPage'], $pagination['currentPage'] + 2); $i++)
                                        @if ($i === $pagination['currentPage'])
                                            <span class="pagination-number active">{{ $i }}</span>
                                        @else
                                            <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                                class="pagination-number">{{ $i }}</a>
                                        @endif
                                    @endfor
                                </div>

                                @if ($pagination['currentPage'] < $pagination['lastPage'])
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['currentPage'] + 1]) }}"
                                        class="pagination-btn">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
