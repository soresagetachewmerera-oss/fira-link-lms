@php
    $pageTitle = 'Completed Enrollments';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['name' => 'Enrollments', 'route' => 'admin.enrollments.index'],
        ['name' => 'Completed Enrollments', 'route' => null]
    ];
@endphp
@extends('admin.enrollments.index')

