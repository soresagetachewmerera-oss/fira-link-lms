@extends('layouts.admin')

@section('title', 'Create Bundle')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.bundles.index') }}">Bundles</a></div>
        <div class="breadcrumb-item active">Create</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Create New Bundle</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.bundles.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Title</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control" name="title" value="{{ old('title') }}" required>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Description</label>
                            <div class="col-sm-12 col-md-7">
                                <textarea class="form-control summernote-simple" name="description" required>{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Price</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="number" step="0.01" class="form-control" name="price" value="{{ old('price', 0) }}" required>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Select Courses</label>
                            <div class="col-sm-12 col-md-7">
                                <select class="form-control select2" multiple name="courses[]" required>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }} ({{ currency_format($course->price) }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Select at least 2 courses for this bundle.</small>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Featured Image</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="file" class="form-control" name="featured_image" accept="image/*">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Status</label>
                            <div class="col-sm-12 col-md-7">
                                <select class="form-control selectric" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
                            <div class="col-sm-12 col-md-7">
                                <button class="btn btn-primary" type="submit">Publish Bundle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
