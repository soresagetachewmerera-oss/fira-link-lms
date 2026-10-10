@extends('layouts.admin')

@section('title', 'Banners')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Banners</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>All Banners</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Banner
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="dataTable">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($banners as $banner)
                                <tr>
                                    <td>{{ $banner->order }}</td>
                                    <td>
                                        <img src="{{ asset('storage/'.$banner->image_path) }}" alt="Banner" width="120" class="rounded">
                                    </td>
                                    <td>
                                        <strong>{{ $banner->title ?? 'N/A' }}</strong>
                                        @if($banner->subtitle)
                                            <div class="text-muted small">{{Str::limit($banner->subtitle, 50)}}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($banner->is_active)
                                            <div class="badge badge-success">Active</div>
                                        @else
                                            <div class="badge badge-danger">Inactive</div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection