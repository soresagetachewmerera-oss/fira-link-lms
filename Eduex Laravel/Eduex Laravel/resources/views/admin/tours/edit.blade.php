@extends('layouts.admin')

@section('title', 'Edit Tour: ' . $tour->title)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tours.index') }}">Tours</a></div>
        <div class="breadcrumb-item active">Edit</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Edit Tour: {{ $tour->title }}</h4>
            <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-eye me-1"></i> View Details
            </a>
        </div>

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.tours.update', $tour) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Basic Info -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Tour Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $tour->title) }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" value="{{ old('category', $tour->category) }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Destination City / Region <span class="text-danger">*</span></label>
                            <input type="text" name="destination" value="{{ old('destination', $tour->destination) }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Meeting Point</label>
                            <input type="text" name="meeting_point" value="{{ old('meeting_point', $tour->meeting_point) }}" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Schedule & Pricing -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Duration (Days) <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" value="{{ old('duration_days', $tour->duration_days) }}" min="1" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Total Available Seats <span class="text-danger">*</span></label>
                            <input type="number" name="total_seats" value="{{ old('total_seats', $tour->total_seats) }}" min="1" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Price per Person <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', $tour->price) }}" min="0" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Currency</label>
                            <input type="text" name="currency" value="{{ old('currency', $tour->currency) }}" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date', optional($tour->start_date)->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date', optional($tour->end_date)->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Departure Time</label>
                            <input type="text" name="departure_time" value="{{ old('departure_time', $tour->departure_time) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Difficulty Level</label>
                            <select name="difficulty_level" class="form-control">
                                <option value="Easy" {{ $tour->difficulty_level == 'Easy' ? 'selected' : '' }}>Easy</option>
                                <option value="Moderate" {{ $tour->difficulty_level == 'Moderate' ? 'selected' : '' }}>Moderate</option>
                                <option value="Challenging" {{ $tour->difficulty_level == 'Challenging' ? 'selected' : '' }}>Challenging</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="form-group">
                    <label>Summary (Brief tagline)</label>
                    <textarea name="summary" rows="2" class="form-control">{{ old('summary', $tour->summary) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Full Detailed Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="6" class="form-control" required>{{ old('description', $tour->description) }}</textarea>
                </div>

                <!-- Cover Image -->
                <div class="form-group">
                    <label>Featured Cover Image</label>
                    @if($tour->featured_image)
                        <div class="mb-2">
                            <img src="{{ $tour->featured_image_url }}" alt="Current Cover" style="max-height: 120px; border-radius: 8px;">
                        </div>
                    @endif
                    <input type="file" name="featured_image" accept="image/*" class="form-control-file">
                    <small class="text-muted d-block">Leave blank to keep existing cover.</small>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Itinerary Schedule</h5>

                <div id="itineraryContainer">
                    @if(!empty($tour->itinerary) && count($tour->itinerary) > 0)
                        @foreach($tour->itinerary as $idx => $step)
                            <div class="itinerary-row border rounded p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="font-weight-600 mb-0">Day {{ $step['day'] ?? ($idx + 1) }} Title</label>
                                    @if($idx > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.itinerary-row').remove();">Remove</button>
                                    @endif
                                </div>
                                <input type="text" name="itinerary_titles[]" value="{{ $step['title'] ?? '' }}" class="form-control mb-2">
                                <label>Day {{ $step['day'] ?? ($idx + 1) }} Activities Description</label>
                                <textarea name="itinerary_descriptions[]" rows="2" class="form-control">{{ $step['description'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                    @else
                        <div class="itinerary-row border rounded p-3 mb-3 bg-light">
                            <div class="form-group mb-2">
                                <label class="font-weight-600">Day 1 Title</label>
                                <input type="text" name="itinerary_titles[]" class="form-control">
                            </div>
                            <div class="form-group mb-0">
                                <label>Day 1 Activities Description</label>
                                <textarea name="itinerary_descriptions[]" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    @endif
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="btnAddItinerary">
                    <i class="fas fa-plus me-1"></i> Add Another Day to Itinerary
                </button>

                <hr class="my-4">
                <h5 class="mb-3">Inclusions, Exclusions &amp; Requirements</h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Included Services (1 per line)</label>
                            <textarea name="included_services_text" rows="4" class="form-control">{{ old('included_services_text', is_array($tour->included_services) ? implode("\n", $tour->included_services) : '') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Exclusions (1 per line)</label>
                            <textarea name="exclusions_text" rows="4" class="form-control">{{ old('exclusions_text', is_array($tour->exclusions) ? implode("\n", $tour->exclusions) : '') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Requirements (1 per line)</label>
                            <textarea name="requirements_text" rows="4" class="form-control">{{ old('requirements_text', is_array($tour->requirements) ? implode("\n", $tour->requirements) : '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Cancellation Policy Terms</label>
                    <textarea name="cancellation_policy" rows="2" class="form-control">{{ old('cancellation_policy', $tour->cancellation_policy) }}</textarea>
                </div>

                <!-- Switches -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_published" value="1" class="custom-control-input" id="isPublishedCheck" {{ $tour->is_published ? 'checked' : '' }}>
                            <label class="custom-control-label" for="isPublishedCheck">Publish Tour (Visible to customers)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_featured" value="1" class="custom-control-input" id="isFeaturedCheck" {{ $tour->is_featured ? 'checked' : '' }}>
                            <label class="custom-control-label" for="isFeaturedCheck">Mark as Featured Expedition</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background-color: #6D1A95; border-color: #6D1A95;">
                        <i class="fas fa-save me-1"></i> Update Tour
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('btnAddItinerary').addEventListener('click', function() {
            const container = document.getElementById('itineraryContainer');
            const totalRows = container.querySelectorAll('.itinerary-row').length + 1;
            const row = document.createElement('div');
            row.className = 'itinerary-row border rounded p-3 mb-3 bg-light';
            row.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="font-weight-600 mb-0">Day ${totalRows} Title</label>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.itinerary-row').remove();">Remove</button>
                </div>
                <input type="text" name="itinerary_titles[]" class="form-control mb-2" placeholder="e.g. Program for Day ${totalRows}">
                <label>Day ${totalRows} Activities Description</label>
                <textarea name="itinerary_descriptions[]" rows="2" class="form-control" placeholder="Activities..."></textarea>
            `;
            container.appendChild(row);
        });
    </script>
    @endpush
@endsection
