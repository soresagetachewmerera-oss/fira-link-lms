@extends('layouts.admin')

@section('title', 'Edit Blog Post')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.blog-posts.index') }}">Blog Posts</a></div>
        <div class="breadcrumb-item">Edit</div>
    </div>
@endsection

@section('main-content')
    @include('admin.blogs.partials.form', [
        'action' => route('admin.blog-posts.update', $blog),
        'method' => 'PUT',
        'submitLabel' => 'Update Post',
        'tagString' => $tagString ?? '',
    ])
@endsection

@push('scripts')
    @include('admin.blogs.partials.scripts')
@endpush
