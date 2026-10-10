@extends('layouts.admin')

@section('title', 'Edit Course Category')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.course-categories.index') }}">Course Categories</a></div>
        <div class="breadcrumb-item">Edit</div>
    </div>
@endsection

@section('main-content')
    @include('admin.course-categories.partials.form', [
        'action' => route('admin.course-categories.update', $category),
        'method' => 'PUT',
        'submitLabel' => 'Update Category',
    ])
@endsection

@push('scripts')
    @include('admin.course-categories.partials.scripts')
@endpush
