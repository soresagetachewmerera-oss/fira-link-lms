@extends('layouts.admin')

@section('title', 'Create Blog Category')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.blog-categories.index') }}">Blog Categories</a></div>
        <div class="breadcrumb-item">Create</div>
    </div>
@endsection

@section('main-content')
    @include('admin.blog-categories.partials.form', [
        'action' => route('admin.blog-categories.store'),
        'method' => null,
        'submitLabel' => 'Create Category',
    ])
@endsection

@push('scripts')
    @include('admin.blog-categories.partials.scripts')
@endpush
