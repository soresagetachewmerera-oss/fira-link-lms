@extends('layouts.admin')

@section('title', 'Create Menu')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Frontend Manager</div>
        <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Menus</a></div>
        <div class="breadcrumb-item">Create</div>
    </div>
@endsection

@section('main-content')
    @include('admin.frontend.menus.partials.form', [
        'action' => route('admin.frontend.menus.store'),
        'method' => null,
        'submitLabel' => 'Create Menu',
    ])
@endsection
