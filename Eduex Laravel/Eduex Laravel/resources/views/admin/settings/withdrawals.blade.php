@extends('layouts.admin')

@section('title', 'Withdrawal Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item">Withdrawal Settings</div>
    </div>
@endsection

@section('main-content')
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="section-title mb-1">Withdrawal Settings</h2>
                    <p class="section-lead mb-0 mt-2">
                        Configure the minimum payout threshold instructors must reach before requesting a withdrawal.
                    </p>
                </div>
                <div class="mb-3 mb-md-0">
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Minimum Payout Threshold</h4>
                    </div>
                    <form action="{{ route('admin.settings.withdrawals.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="minimum_amount">Minimum Amount ({{ currency_code() }})</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('minimum_amount') is-invalid @enderror" id="minimum_amount"
                                    name="minimum_amount"
                                    value="{{ old('minimum_amount', $settings['minimum_amount'] ?? 10) }}" required>
                                @error('minimum_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Instructors must have at least this amount in their wallet before submitting a
                                    withdrawal request.
                                </small>
                            </div>

                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Current platform currency: <strong>{{ currency_code() }}</strong>. You can change the
                                currency under Platform Configuration.
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
