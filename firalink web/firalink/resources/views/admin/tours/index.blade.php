@extends('layouts.admin')

@section('title', 'Tours & Field Expeditions')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Tours</div>
    </div>
@endsection

@section('main-content')
    <!-- Operational Statistics -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-route"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Tours</h4></div>
                    <div class="card-body">{{ number_format($stats['total_tours']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Published</h4></div>
                    <div class="card-body">{{ number_format($stats['published_tours']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-ticket-alt"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Bookings</h4></div>
                    <div class="card-body">{{ number_format($stats['total_bookings']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-coins"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Revenue (ETB)</h4></div>
                    <div class="card-body">{{ number_format($stats['total_revenue'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">All Tours &amp; Origin Expeditions</h4>
            <div>
                <a href="{{ route('admin.tours.bookings.index') }}" class="btn btn-outline-info me-2">
                    <i class="fas fa-clipboard-list me-1"></i> View All Bookings
                </a>
                <a href="{{ route('admin.tours.create') }}" class="btn btn-primary" style="background-color: #6D1A95; border-color: #6D1A95;">
                    <i class="fas fa-plus me-1"></i> Create New Tour
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.tours.index') }}" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by title, destination, category...">
                    </div>
                    <div class="col-md-3">
                        <select name="published" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="1" {{ request('published') === '1' ? 'selected' : '' }}>Published Only</option>
                            <option value="0" {{ request('published') === '0' ? 'selected' : '' }}>Hidden Only</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                        <a href="{{ route('admin.tours.index') }}" class="btn btn-outline-secondary">Reset</a>
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

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-striped table-md">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tour Title</th>
                            <th>Destination</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Seats (Booked/Total)</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tours as $tour)
                            <tr>
                                <td>{{ $loop->iteration + ($tours->currentPage() - 1) * $tours->perPage() }}</td>
                                <td>
                                    <div class="font-weight-600">{{ $tour->title }}</div>
                                    <small class="text-muted">{{ $tour->category ?? 'General Expedition' }} &bull; {{ $tour->difficulty_level }}</small>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> {{ $tour->destination }}
                                </td>
                                <td>{{ $tour->duration_days }} {{ Str::plural('Day', $tour->duration_days) }}</td>
                                <td class="font-weight-600 text-purple" style="color: #6D1A95;">
                                    {{ $tour->currency }} {{ number_format($tour->price, 2) }}
                                </td>
                                <td>
                                    @php
                                        $booked = $tour->bookings_count;
                                        $remaining = $tour->remainingSeats();
                                    @endphp
                                    <span class="badge badge-light border">
                                        {{ $booked }} / {{ $tour->total_seats }}
                                    </span>
                                    <small class="text-success d-block">({{ $remaining }} left)</small>
                                </td>
                                <td>
                                    <form action="{{ route('admin.tours.toggle-publish', $tour) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $tour->is_published ? 'btn-success' : 'btn-secondary' }}" title="Click to toggle publish">
                                            {{ $tour->is_published ? 'Published' : 'Hidden' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-sm btn-info" title="View details & attendees">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.tours.edit', $tour) }}" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.tours.destroy', $tour) }}" method="POST" onsubmit="return confirm('Delete this tour?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No tours found matching the criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-center">
                {{ $tours->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
