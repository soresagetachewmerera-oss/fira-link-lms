@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <div class="section-title wow fadeInUp mb-4">
                    <h6>Edit Bundle</h6>
                    <h2>{{ $bundle->title }}</h2>
                </div>

                <div class="dashboard-content-section p-4">
                    <form action="{{ route('instructor.bundles.update', $bundle->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Bundle Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $bundle->title) }}" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control summernote" id="description" name="description" rows="5" required>{{ old('description', $bundle->description) }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price ({{ currency_symbol() }})</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price', $bundle->price) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select w-100" id="status" name="status" style="border: 1px solid #ced4da; border-radius: .25rem; padding: .375rem .75rem;" required>
                                    <option value="active" {{ $bundle->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $bundle->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="courses" class="form-label">Select Courses to Include</label>
                                <select class="form-select select2 w-100" id="courses" name="courses[]" multiple style="border: 1px solid #ced4da; border-radius: .25rem; padding: .375rem .75rem;" required>
                                    @php
                                        $selectedCourses = $bundle->courses->pluck('id')->toArray();
                                    @endphp
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ in_array($course->id, $selectedCourses) ? 'selected' : '' }}>
                                            {{ $course->title }} ({{ currency_format($course->price) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Hold CTRL/CMD to select multiple. Minimum 2 required. Note: Updating bundles might revert status to pending admin approval.</small>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label">Featured Image</label>
                                @if($bundle->featured_image)
                                    <div class="mb-3">
                                        <img src="{{ asset('storage/' . $bundle->featured_image) }}" alt="Preview" class="img-thumbnail" width="150">
                                    </div>
                                @endif
                                <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="theme-btn w-100"><i class="fas fa-save"></i> Update Bundle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
