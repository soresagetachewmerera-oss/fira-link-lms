@extends('layouts.admin')

@section('title', 'Events')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Events</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-calendar"></i></div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Events</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['total']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-bullhorn"></i></div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Published</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['published']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-forward"></i></div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Upcoming</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['upcoming']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Manage Events</h4>
            <div class="card-header-form">
                <form method="GET" action="{{ route('admin.events.index') }}" class="form-inline">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="form-group mr-2 mb-2">
                            <input type="search" class="form-control" name="search" placeholder="Search title"
                                value="{{ request('search') }}">
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="published" @selected(request('status') === 'published')>Published</option>
                                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <select name="timeline" class="form-control">
                                <option value="">All Dates</option>
                                <option value="upcoming" @selected(request('timeline') === 'upcoming')>Upcoming</option>
                                <option value="past" @selected(request('timeline') === 'past')>Past</option>
                            </select>
                        </div>
                        <button class="btn btn-primary mb-2" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Schedule</th>
                        <th>Location</th>
                        <th>Seats</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $event->title }}</div>
                                <div class="text-muted text-sm">{{ $event->slug }}</div>
                            </td>
                            <td>
                                <div>{{ $event->start_date?->format('M d, Y') }} -
                                    {{ $event->end_date?->format('M d, Y') }}</div>
                                <div class="text-muted text-sm">{{ $event->start_time?->format('H:i') }} -
                                    {{ $event->end_time?->format('H:i') }}</div>
                            </td>
                            <td>
                                <div>{{ Str::limit($event->location ?? $event->location_description, 60) }}</div>
                            </td>
                            <td>
                                @if($event->total_seats)
                                    {{ $event->confirmed_bookings_count }}/{{ $event->total_seats }}
                                @else
                                    <span class="badge badge-light">Unlimited</span>
                                @endif
                            </td>
                            <td>{{ $event->price ? currency_format($event->price, 2, 'symbol') : 'Free' }}</td>
                            <td>
                                <span class="badge badge-{{ $event->is_published ? 'success' : 'secondary' }}">
                                    {{ $event->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <form action="{{ route('admin.events.toggle-publish', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $event->is_published ? 'warning' : 'success' }}">
                                        <i class="fas fa-toggle-{{ $event->is_published ? 'off' : 'on' }}"></i>
                                        {{ $event->is_published ? 'Unpublish' : 'Publish' }}
                                    </button>
                                </form>
                                <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No events found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer text-right">
            {{ $events->links('vendor.pagination.stisla-default') }}
        </div>
    </div>
@endsection

