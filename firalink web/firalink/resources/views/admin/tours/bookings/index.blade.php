@extends('layouts.admin')

@section('title', 'Manage Tour Bookings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tours.index') }}">Tours</a></div>
        <div class="breadcrumb-item active">Bookings</div>
    </div>
@endsection

@section('main-content')
    <!-- Stats Row -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-receipt"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Bookings</h4></div>
                    <div class="card-body">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Confirmed</h4></div>
                    <div class="card-body">{{ number_format($stats['confirmed']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Pending</h4></div>
                    <div class="card-body">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-money-bill"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Paid Total</h4></div>
                    <div class="card-body">{{ number_format($stats['revenue'], 2) }} ETB</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Customer Tour Reservations</h4>
            <a href="{{ route('admin.tours.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Tours
            </a>
        </div>

        <div class="card-body">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.tours.bookings.index') }}" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference, name, email, phone...">
                    </div>
                    <div class="col-md-3">
                        <select name="tour_id" class="form-control">
                            <option value="">All Tours</option>
                            @foreach($tours as $t)
                                <option value="{{ $t->id }}" {{ request('tour_id') == $t->id ? 'selected' : '' }}>
                                    {{ Str::limit($t->title, 40) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="{{ route('admin.tours.bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-md">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Tour</th>
                            <th>Passenger Details</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $b)
                            <tr>
                                <td class="font-weight-600 font-monospace">{{ $b->reference }}</td>
                                <td>
                                    <div class="font-weight-600">{{ $b->tour->title ?? 'Tour' }}</div>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt"></i> {{ $b->tour->destination ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $b->full_name }}</strong>
                                    <small class="d-block text-muted">{{ $b->email }}</small>
                                    @if($b->phone)
                                        <small class="text-muted">{{ $b->phone }}</small>
                                    @endif
                                </td>
                                <td>{{ $b->participants }}</td>
                                <td class="font-weight-600 text-purple" style="color: #6D1A95;">
                                    {{ $b->currency }} {{ number_format($b->total_amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $b->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                        {{ strtoupper($b->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $b->status === 'confirmed' ? 'badge-success' : ($b->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                                        {{ strtoupper($b->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($b->booked_at)->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tours.bookings.show', $b) }}" class="btn btn-sm btn-info" title="Manage booking">
                                        <i class="fas fa-eye"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No customer bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-center">
                {{ $bookings->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
