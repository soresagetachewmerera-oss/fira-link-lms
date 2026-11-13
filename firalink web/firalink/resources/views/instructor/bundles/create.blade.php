@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <div class="section-title wow fadeInUp mb-4">
                    <h6>Create Bundle</h6>
                    <h2>Offer A New Course Bundle</h2>
                </div>

                <div class="dashboard-content-section p-4">
                    <form action="{{ route('instructor.bundles.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Bundle Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control summernote" id="description" name="description" rows="5" required>{{ old('description') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price ({{ currency_symbol() }})</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price', 0) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select w-100" id="status" name="status" style="border: 1px solid #ced4da; border-radius: .25rem; padding: .375rem .75rem;" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="courses" class="form-label">Select Courses to Include</label>
                                <select class="form-select select2 w-100" id="courses" name="courses[]" multiple style="border: 1px solid #ced4da; border-radius: .25rem; padding: .375rem .75rem;" required>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }} ({{ currency_format($course->price) }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Hold CTRL/CMD to select multiple. Minimum 2 required. Note: Adding bundles triggers admin approval.</small>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label for="featured_image" class="form-label">Featured Image</label>
                                <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="theme-btn w-100"><i class="fas fa-layer-group"></i> Create Bundle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
