@extends('layouts.admin')

@section('title', 'Withdrawal Details')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.withdrawals.index') }}">Payout Requests</a></div>
        <div class="breadcrumb-item">Withdrawal #{{ $withdrawal->reference }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Withdrawal #{{ $withdrawal->reference }}</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary"><i
                                class="fas fa-arrow-left mr-2"></i> Back to list</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <h6>Instructor</h6>
                            <p class="mb-1 font-weight-bold">{{ $withdrawal->instructor->name ?? 'Unknown' }}</p>
                            <p class="text-muted mb-0">{{ $withdrawal->instructor->email ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Amount</h6>
                            <p class="mb-1 font-weight-bold">{{ currency_format($withdrawal->amount) }}</p>
                            <p class="text-muted mb-0">{{ ucfirst(str_replace('_', ' ', $withdrawal->method)) }}</p>
                            <p class="text-muted mb-0">{{ $withdrawal->method_details ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Status</h6>
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'rejected' => 'danger',
                                ];
                            @endphp
                            <p>
                                <span class="badge badge-{{ $statusColors[$withdrawal->status] ?? 'secondary' }}">
                                    {{ ucfirst($withdrawal->status) }}
                                </span>
                            </p>
                            <p class="mb-0 text-muted">
                                Requested:
                                {{ optional($withdrawal->requested_at)->format('M d, Y H:i') ?? $withdrawal->created_at->format('M d, Y H:i') }}<br>
                                Processed: {{ optional($withdrawal->processed_at)->format('M d, Y H:i') ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Instructor Notes</h6>
                            <div class="border rounded p-3 bg-light">
                                {{ $withdrawal->notes ?: 'No notes provided.' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Admin Notes</h6>
                            <div class="border rounded p-3 bg-light">
                                {{ $withdrawal->admin_notes ?: 'No admin notes yet.' }}
                            </div>
                        </div>
                    </div>

                    @if (in_array($withdrawal->status, [
                            \App\Models\InstructorWithdrawal::STATUS_PENDING,
                            \App\Models\InstructorWithdrawal::STATUS_PROCESSING,
                        ]))
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Update Status</h6>
                            </div>
                            <div class="col-md-6">
                                <form action="{{ route('admin.withdrawals.status', $withdrawal) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="processing"
                                                {{ $withdrawal->status === 'processing' ? 'selected' : '' }}>Processing
                                            </option>
                                            <option value="completed">Completed</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Admin Notes (optional)</label>
                                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add details for audit trail...">{{ old('admin_notes', $withdrawal->admin_notes) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Update Status
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
