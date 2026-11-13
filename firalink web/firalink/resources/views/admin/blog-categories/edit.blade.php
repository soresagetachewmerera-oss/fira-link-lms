@extends('layouts.admin')

@section('title', 'Edit Blog Category')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.blog-categories.index') }}">Blog Categories</a></div>
        <div class="breadcrumb-item">Edit</div>
    </div>
@endsection

@section('main-content')
    @include('admin.blog-categories.partials.form', [
        'action' => route('admin.blog-categories.update', $category),
        'method' => 'PUT',
        'submitLabel' => 'Update Category',
    ])
@endsection

@push('scripts')
    @include('admin.blog-categories.partials.scripts')
@endpush
