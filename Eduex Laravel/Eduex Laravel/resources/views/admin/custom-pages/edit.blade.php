@extends('layouts.admin')

@section('title', 'Edit Page')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.custom-pages.index') }}">Custom Pages</a></div>
    <div class="breadcrumb-item">{{ $page->title }}</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit: {{ $page->title }}</h4>
                </div>
                <form method="POST" action="{{ route('admin.custom-pages.update', $page) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $page->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Short Description <span class="text-muted">(Optional)</span></label>
                            <input type="text" id="description" name="description"
                                class="form-control @error('description') is-invalid @enderror"
                                value="{{ old('description', $page->description) }}">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" class="form-control summernote @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('admin.custom-pages.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 320,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'italic', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        });
    </script>
@endpush
