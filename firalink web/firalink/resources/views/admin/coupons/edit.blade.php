@extends('layouts.admin')

@section('title', 'Edit Coupon')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.coupons.index') }}">Coupons</a></div>
        <div class="breadcrumb-item">Edit</div>
    </div>
@endsection

@section('main-content')
    @include('admin.coupons.partials.form', [
        'action' => route('admin.coupons.update', $coupon->id),
        'method' => 'PUT',
        'submitLabel' => 'Update Coupon',
    ])
@endsection
