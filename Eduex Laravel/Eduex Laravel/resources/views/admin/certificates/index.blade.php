@extends('layouts.admin')

@section('title', 'Certificates')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Certificates</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Issued</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['total'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Last 7 Days</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['last_seven_days'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>This Month</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['this_month'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Certificates</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.certificates.index') }}" class="mb-4">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="sr-only">Search</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control"
                                placeholder="Search by student or course" value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_from" class="sr-only">Date from</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                            value="{{ $dateFrom }}" placeholder="From">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_to" class="sr-only">Date to</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}"
                            placeholder="To">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-center">
                        @if ($search || $dateFrom || $dateTo)
                            <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($certificates->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Instructor</th>
                                <th>Completed At</th>
                                <th>Certificate #</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($certificates as $certificate)
                                <tr>
                                    <td>
                                        <strong>{{ $certificate->user?->name ?? '—' }}</strong>
                                        <div class="text-muted small">{{ $certificate->user?->email ?? '—' }}</div>
                                    </td>
                                    <td>
                                        {{ $certificate->course?->title ?? '—' }}
                                    </td>
                                    <td>{{ $certificate->course?->instructor?->name ?? '—' }}</td>
                                    <td>{{ $certificate->completion_date ?? '—' }}</td>
                                    <td><code>{{ $certificate->certificate_number ?? '—' }}</code></td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.certificates.download', $certificate) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            Showing {{ $certificates->firstItem() ?? 0 }} to {{ $certificates->lastItem() ?? 0 }} of
                            {{ $certificates->total() }} certificates
                        </p>
                    </div>
                    <div>
                        {{ $certificates->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No certificates found.</p>
            @endif
        </div>
    </div>
@endsection
