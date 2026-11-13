@extends('layouts.admin')

@section('title', 'Course Languages')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Course Languages</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-language"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Languages</h4>
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
                    <i class="fas fa-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Active</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['active'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-pause"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Inactive</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['inactive'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Course Languages</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.course-languages.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Language
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.course-languages.index') }}" class="mb-4">
                <div class="form-row">
                    <div class="col-md-5 mb-3">
                        <label for="search" class="sr-only">Search</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control"
                                placeholder="Search languages..." value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="status" class="sr-only">Status</label>
                        <select class="form-control" id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        @if ($search || $status)
                            <a href="{{ route('admin.course-languages.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($languages->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Native Name</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Updated</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($languages as $language)
                                <tr>
                                    <td>{{ $language->name }}</td>
                                    <td><code>{{ $language->code }}</code></td>
                                    <td>{{ $language->native_name ?? '—' }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $language->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $language->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $language->display_order }}</td>
                                    <td>{{ $language->updated_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.course-languages.edit', $language) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.course-languages.destroy', $language) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Delete this language? Courses assigned to it will keep their text label.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            Showing {{ $languages->firstItem() ?? 0 }} to {{ $languages->lastItem() ?? 0 }} of
                            {{ $languages->total() }} languages
                        </p>
                    </div>
                    <div>
                        {{ $languages->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No languages found.</p>
            @endif
        </div>
    </div>
@endsection
