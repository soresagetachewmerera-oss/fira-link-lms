@php
    $pageTitle = 'Pending Enrollments';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['name' => 'Enrollments', 'route' => 'admin.enrollments.index'],
        ['name' => 'Pending Enrollments', 'route' => null]
    ];
@endphp
@extends('admin.enrollments.index')

