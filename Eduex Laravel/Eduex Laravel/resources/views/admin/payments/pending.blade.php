@php
    $pageTitle = 'Pending Payments';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['name' => 'Payments', 'route' => 'admin.payments.index'],
        ['name' => 'Pending Payments', 'route' => null]
    ];
@endphp
@extends('admin.payments.index')

