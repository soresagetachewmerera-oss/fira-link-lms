@extends('layouts.admin')

@section('title', 'Create Banner')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.banners.index') }}">Banners</a></div>
        <div class="breadcrumb-item">Create</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Create New Banner</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Banner Image <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-12 col-md-7">
                                <input type="file" class="form-control @error('image') is-invalid @enderror" name="image"
                                    required accept="image/*">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Title</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title"
                                    value="{{ old('title') }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Subtitle</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control @error('subtitle') is-invalid @enderror"
                                    name="subtitle" value="{{ old('subtitle') }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Link</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="url" class="form-control @error('link') is-invalid @enderror" name="link"
                                    value="{{ old('link') }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Button Text</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control @error('button_text') is-invalid @enderror"
                                    name="button_text" value="{{ old('button_text', 'Learn More') }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Display Order</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="number" class="form-control @error('order') is-invalid @enderror" name="order"
                                    value="{{ old('order', 0) }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Status</label>
                            <div class="col-sm-12 col-md-7">
                                <label class="custom-switch mt-2">
                                    <input type="checkbox" name="is_active" class="custom-switch-input" checked>
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description">Active</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
                            <div class="col-sm-12 col-md-7">
                                <button type="submit" class="btn btn-primary">Create Banner</button>
                                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection