@extends('layouts.admin')

@section('title', $blog->title)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.blog-posts.index') }}">Blog Posts</a></div>
        <div class="breadcrumb-item">View</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ $blog->title }}</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.blog-posts.edit', $blog) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <span
                            class="badge badge-{{ $blog->is_published && $blog->published_at && $blog->published_at->isPast() ? 'success' : ($blog->is_published ? 'info' : 'warning') }}">
                            @if ($blog->is_published && $blog->published_at && $blog->published_at->isFuture())
                                Scheduled
                            @elseif($blog->is_published && $blog->published_at && $blog->published_at->isPast())
                                Published
                            @else
                                Draft
                            @endif
                        </span>
                        @if ($blog->category_label)
                            <span class="badge badge-secondary">{{ $blog->category_label }}</span>
                        @endif
                        @if ($blog->reading_time_minutes)
                            <span class="badge badge-light"><i class="fas fa-book-reader"></i>
                                {{ $blog->reading_time_minutes }} min read</span>
                        @endif
                    </div>

                    @if ($blog->featured_image)
                        @php
                            $imageUrl = \Illuminate\Support\Str::startsWith($blog->featured_image, [
                                'http://',
                                'https://',
                            ])
                                ? $blog->featured_image
                                : (\Illuminate\Support\Str::startsWith($blog->featured_image, 'assets/')
                                    ? asset($blog->featured_image)
                                    : asset('storage/' . $blog->featured_image));
                        @endphp
                        <div class="mb-4">
                            <img src="{{ $imageUrl }}" alt="Featured image" class="img-fluid rounded">
                        </div>
                    @endif

                    @if ($blog->excerpt)
                        <p class="lead">{{ $blog->excerpt }}</p>
                    @endif

                    <div class="content">
                        {!! $blog->content !!}
                    </div>
                </div>
            </div>

            @if ($recentComments->count())
                <div class="card">
                    <div class="card-header">
                        <h4>Latest Comments</h4>
                        <div class="card-header-action">
                            <a href="{{ route('admin.blog-comments.index', ['post_id' => $blog->id]) }}"
                                class="btn btn-outline-primary btn-sm">
                                Manage comments
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach ($recentComments as $comment)
                            <div class="media mb-4">
                                <img class="mr-3 rounded-circle" width="45"
                                    src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($comment->author_email))) }}?s=90&d=mp"
                                    alt="{{ $comment->author_name }}">
                                <div class="media-body">
                                    <h5 class="mt-0 mb-1">
                                        {{ $comment->author_name }}
                                        <small class="text-muted"> • {{ $comment->created_at->diffForHumans() }}</small>
                                        @if (!$comment->is_approved)
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </h5>
                                    {!! nl2br(e(\Illuminate\Support\Str::limit($comment->body, 300))) !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Post Meta</h4>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Author:</strong> {{ $blog->author_name ?? '—' }}</p>
                    <p class="mb-2"><strong>Published at:</strong>
                        {{ $blog->published_at ? $blog->published_at->format('M d, Y g:i A') : '—' }}</p>
                    <p class="mb-2"><strong>Created:</strong> {{ $blog->created_at?->format('M d, Y g:i A') ?? '—' }}</p>
                    <p class="mb-2"><strong>Updated:</strong> {{ $blog->updated_at?->diffForHumans() ?? '—' }}</p>
                    <p class="mb-2"><strong>Approved comments:</strong> {{ $blog->approved_comments_count }}</p>
                    <p class="mb-2"><strong>Total comments:</strong> {{ $blog->comments_count }}</p>
                    <p class="mb-2"><strong>Category:</strong>
                        {{ $blog->categoryRelation?->name ?? ($blog->category ?? '—') }}</p>
                    @if (!empty($blog->tags))
                        <p class="mb-0"><strong>Tags:</strong></p>
                        <div class="mt-1">
                            @foreach ($blog->tags as $tag)
                                <span class="badge badge-light">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
