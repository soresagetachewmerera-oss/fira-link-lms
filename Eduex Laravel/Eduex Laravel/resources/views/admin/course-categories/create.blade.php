@extends('layouts.admin')

@section('title', 'Create Course Category')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.course-categories.index') }}">Course Categories</a></div>
        <div class="breadcrumb-item">Create</div>
    </div>
@endsection

@section('main-content')
    @include('admin.course-categories.partials.form', [
        'action' => route('admin.course-categories.store'),
        'method' => null,
        'submitLabel' => 'Create Category',
    ])
@endsection

@push('scripts')
    @include('admin.course-categories.partials.scripts')
@endpush
