@extends('layouts.admin')

@section('title', 'Blog Comments')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Blog Comments</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Comments</h4>
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
                        <h4>Approved</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['approved'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['pending'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Manage Comments</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.blog-comments.index') }}" class="mb-4">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="sr-only">Search</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control"
                                placeholder="Search comments..." value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="status" class="sr-only">Status</label>
                        <select class="form-control" id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="post_id" class="sr-only">Blog Post</label>
                        <select class="form-control" id="post_id" name="post_id" onchange="this.form.submit()">
                            <option value="">All Posts</option>
                            @foreach ($posts as $id => $title)
                                <option value="{{ $id }}"
                                    {{ (string) $postId === (string) $id ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Str::limit($title, 45) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        @if ($search || $status || $postId)
                            <a href="{{ route('admin.blog-comments.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($comments->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Author</th>
                                <th>Comment</th>
                                <th>Post</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comments as $comment)
                                <tr>
                                    <td>
                                        <strong>{{ $comment->author_name }}</strong>
                                        <div class="text-muted small">{{ $comment->author_email }}</div>
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($comment->body, 50) }}</td>
                                    <td>
                                        <a href="{{ route('admin.blog-posts.show', $comment->post) }}">
                                            {{ \Illuminate\Support\Str::limit($comment->post->title, 40) }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($comment->is_approved)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $comment->created_at->diffForHumans() }}</td>
                                    <td class="text-right">
                                        <form action="{{ route('admin.blog-comments.toggle', $comment) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="approve"
                                                value="{{ $comment->is_approved ? 0 : 1 }}">
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-{{ $comment->is_approved ? 'secondary' : 'success' }}">
                                                <i class="fas {{ $comment->is_approved ? 'fa-ban' : 'fa-check' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.blog-comments.destroy', $comment) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Delete this comment?');">
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
                            Showing {{ $comments->firstItem() ?? 0 }} to {{ $comments->lastItem() ?? 0 }} of
                            {{ $comments->total() }} comments
                        </p>
                    </div>
                    <div>
                        {{ $comments->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No comments found.</p>
            @endif
        </div>
    </div>
@endsection
