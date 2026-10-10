@extends('layouts.admin')

@section('title', 'Edit Banner')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.banners.index') }}">Banners</a></div>
        <div class="breadcrumb-item">Edit</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Banner</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.banners.update', $banner->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Banner Image</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="file" class="form-control @error('image') is-invalid @enderror" name="image"
                                    accept="image/*">
                                @if($banner->image_path)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $banner->image_path) }}" alt="Current Image" width="200"
                                            class="img-thumbnail">
                                    </div>
                                @endif
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Title</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title"
                                    value="{{ old('title', $banner->title) }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Subtitle</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control @error('subtitle') is-invalid @enderror"
                                    name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Link</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="url" class="form-control @error('link') is-invalid @enderror" name="link"
                                    value="{{ old('link', $banner->link) }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Button Text</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" class="form-control @error('button_text') is-invalid @enderror"
                                    name="button_text" value="{{ old('button_text', $banner->button_text) }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Display Order</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="number" class="form-control @error('order') is-invalid @enderror" name="order"
                                    value="{{ old('order', $banner->order) }}">
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">Status</label>
                            <div class="col-sm-12 col-md-7">
                                <label class="custom-switch mt-2">
                                    <input type="checkbox" name="is_active" class="custom-switch-input"
                                        {{ $banner->is_active ? 'checked' : '' }}>
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description">Active</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
                            <div class="col-sm-12 col-md-7">
                                <button type="submit" class="btn btn-primary">Update Banner</button>
                                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection