@extends('layouts.admin')

@section('title', 'Edit Bundle')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.bundles.index') }}">Bundles</a></div>
        <div class="breadcrumb-item active">Edit</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Bundle: {{ $bundle->title }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.bundles.update', $bundle->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Title</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control" name="title" value="{{ old('title', $bundle->title) }}" required>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Description</label>
                            <div class="col-sm-12 col-md-7">
                                <textarea class="form-control summernote-simple" name="description" required>{{ old('description', $bundle->description) }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Price</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="number" step="0.01" class="form-control" name="price" value="{{ old('price', $bundle->price) }}" required>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Select Courses</label>
                            <div class="col-sm-12 col-md-7">
                                <select class="form-control select2" multiple name="courses[]" required>
                                    @php
                                        $selectedCourses = $bundle->courses->pluck('id')->toArray();
                                    @endphp
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ in_array($course->id, $selectedCourses) ? 'selected' : '' }}>
                                            {{ $course->title }} ({{ currency_format($course->price) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Featured Image</label>
                            <div class="col-sm-12 col-md-7">
                                @if($bundle->featured_image)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $bundle->featured_image) }}" alt="Current Image" width="100" class="rounded">
                                    </div>
                                @endif
                                <input type="file" class="form-control" name="featured_image" accept="image/*">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Status</label>
                            <div class="col-sm-12 col-md-7">
                                <select class="form-control selectric" name="status" required>
                                    <option value="active" {{ $bundle->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $bundle->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
                            <div class="col-sm-12 col-md-7">
                                <button class="btn btn-primary" type="submit">Update Bundle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
