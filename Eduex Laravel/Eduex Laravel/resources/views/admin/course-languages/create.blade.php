@extends('layouts.admin')

@section('title', 'Create Course Language')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.course-languages.index') }}">Course Languages</a></div>
        <div class="breadcrumb-item">Create</div>
    </div>
@endsection

@section('main-content')
    @include('admin.course-languages.partials.form', [
        'action' => route('admin.course-languages.store'),
        'method' => null,
        'submitLabel' => 'Create Language',
    ])
@endsection
