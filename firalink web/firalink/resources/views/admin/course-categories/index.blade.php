@extends('layouts.admin')

@section('title', 'Course Categories')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Course Categories</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Categories</h4>
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
            <h4>Course Categories</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.course-categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Category
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.course-categories.index') }}" class="mb-4">
                <div class="form-row">
                    <div class="col-md-5 mb-3">
                        <label for="search" class="sr-only">Search</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control"
                                placeholder="Search categories..." value="{{ $search }}">
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
                            <a href="{{ route('admin.course-categories.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($categories->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Icon</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Updated</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td>
                                        <strong>{{ $category->name }}</strong>
                                        @if ($category->description)
                                            <div class="text-muted small">
                                                {{ \Illuminate\Support\Str::limit($category->description, 80) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $category->slug }}</td>
                                    <td>{{ $category->icon ?? '—' }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $category->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $category->display_order }}</td>
                                    <td>{{ $category->updated_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.course-categories.edit', $category) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.course-categories.destroy', $category) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Delete this category? Courses assigned to it will keep their text label.');">
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
                            Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of
                            {{ $categories->total() }} categories
                        </p>
                    </div>
                    <div>
                        {{ $categories->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No categories found.</p>
            @endif
        </div>
    </div>
@endsection
