@extends('layouts.admin')

@section('title', 'Blog Posts')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Blog Posts</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Posts</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['total'] }}
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
                        <h4>Published</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['published'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Scheduled</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['scheduled'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-pencil-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Drafts</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['drafts'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Blog Posts</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.blog-posts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    New Post
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.blog-posts.index') }}" class="mb-4">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="sr-only">Search</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control"
                                placeholder="Search posts..." value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="status" class="sr-only">Status</label>
                        <select class="form-control" id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="category_id" class="sr-only">Category</label>
                        <select class="form-control" id="category_id" name="category_id" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            @foreach ($categoryOptions as $categoryOption)
                                <option value="{{ $categoryOption->id }}"
                                    {{ (string) $categoryId === (string) $categoryOption->id ? 'selected' : '' }}>
                                    {{ $categoryOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        @if ($search || $status || $categoryId)
                            <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($posts->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Updated</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr>
                                    <td>
                                        <strong>{{ $post->title }}</strong>
                                        <div class="text-muted small">
                                            {{ \Illuminate\Support\Str::limit($post->excerpt ?? strip_tags($post->content), 80) }}
                                        </div>
                                    </td>
                                    <td>{{ $post->categoryRelation?->name ?? ($post->category ?? '—') }}</td>
                                    <td>
                                        @php
                                            $statusLabel = 'Draft';
                                            $badgeClass = 'badge-warning';
                                            if ($post->is_published && $post->published_at) {
                                                if ($post->published_at->isFuture()) {
                                                    $statusLabel = 'Scheduled';
                                                    $badgeClass = 'badge-info';
                                                } else {
                                                    $statusLabel = 'Published';
                                                    $badgeClass = 'badge-success';
                                                }
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>{{ $post->approved_comments_count }}</td>
                                    <td>
                                        {{ $post->updated_at?->diffForHumans() ?? '—' }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.blog-posts.show', $post) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.blog-posts.edit', $post) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.blog-posts.destroy', $post) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Delete this post? This action cannot be undone.');">
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
                            Showing {{ $posts->firstItem() ?? 0 }} to {{ $posts->lastItem() ?? 0 }} of
                            {{ $posts->total() }} posts
                        </p>
                    </div>
                    <div>
                        {{ $posts->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No blog posts found.</p>
            @endif
        </div>
    </div>
@endsection
