@php
    $pageTitle = 'Pending Reviews';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['name' => 'Reviews', 'route' => 'admin.reviews.index'],
        ['name' => 'Pending Reviews', 'route' => null]
    ];
@endphp
@extends('admin.reviews.index')

