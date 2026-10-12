@extends('layouts.admin')

@section('title', 'Create New Tour Expedition')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tours.index') }}">Tours</a></div>
        <div class="breadcrumb-item active">Create</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Tour Configuration &amp; Metadata</h4>
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

            <form action="{{ route('admin.tours.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Basic Info -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Tour Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" value="{{ old('title') }}" class="form-control" required placeholder="e.g. Yirgacheffe Specialty Coffee Origin Expedition">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" value="{{ old('category') }}" class="form-control" placeholder="e.g. Origin Expedition, Logistics, Heritage">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Destination City / Region <span class="text-danger">*</span></label>
                            <input type="text" name="destination" value="{{ old('destination') }}" class="form-control" required placeholder="e.g. Yirgacheffe, Gedeo Zone, SNNPR">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Meeting Point</label>
                            <input type="text" name="meeting_point" value="{{ old('meeting_point') }}" class="form-control" placeholder="e.g. Fira Link HQ, Bole Sub-City, Addis Ababa">
                        </div>
                    </div>
                </div>

                <!-- Schedule & Pricing -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Duration (Days) <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" value="{{ old('duration_days', 1) }}" min="1" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Total Available Seats <span class="text-danger">*</span></label>
                            <input type="number" name="total_seats" value="{{ old('total_seats', 20) }}" min="1" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Price per Person <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', '0.00') }}" min="0" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Currency</label>
                            <input type="text" name="currency" value="{{ old('currency', 'ETB') }}" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Departure Time</label>
                            <input type="text" name="departure_time" value="{{ old('departure_time', '08:00 AM') }}" class="form-control" placeholder="08:00 AM">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Difficulty Level</label>
                            <select name="difficulty_level" class="form-control">
                                <option value="Easy">Easy</option>
                                <option value="Moderate" selected>Moderate</option>
                                <option value="Challenging">Challenging</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="form-group">
                    <label>Summary (Brief tagline)</label>
                    <textarea name="summary" rows="2" class="form-control" placeholder="One or two sentences summarizing the expedition...">{{ old('summary') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Full Detailed Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="6" class="form-control" required placeholder="Comprehensive narrative describing what participants will learn, visit, and experience...">{{ old('description') }}</textarea>
                </div>

                <!-- Cover Image -->
                <div class="form-group">
                    <label>Featured Cover Image</label>
                    <input type="file" name="featured_image" accept="image/*" class="form-control-file">
                    <small class="text-muted d-block">Recommended dimensions: 1200x800 px. Max size 5MB.</small>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Itinerary Schedule</h5>

                <div id="itineraryContainer">
                    <div class="itinerary-row border rounded p-3 mb-3 bg-light">
                        <div class="form-group mb-2">
                            <label class="font-weight-600">Day 1 Title</label>
                            <input type="text" name="itinerary_titles[]" class="form-control" placeholder="e.g. Departure to Yirgacheffe &amp; Farm Orientation">
                        </div>
                        <div class="form-group mb-0">
                            <label>Day 1 Activities Description</label>
                            <textarea name="itinerary_descriptions[]" rows="2" class="form-control" placeholder="Details of the day's events..."></textarea>
                        </div>
                    </div>
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
                            <textarea name="included_services_text" rows="4" class="form-control" placeholder="Round-trip transport&#10;All meals&#10;Guide fees">{{ old('included_services_text') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Exclusions (1 per line)</label>
                            <textarea name="exclusions_text" rows="4" class="form-control" placeholder="Personal travel insurance&#10;Tips and souvenirs">{{ old('exclusions_text') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Requirements (1 per line)</label>
                            <textarea name="requirements_text" rows="4" class="form-control" placeholder="Hiking shoes&#10;Valid Government ID">{{ old('requirements_text') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Cancellation Policy Terms</label>
                    <textarea name="cancellation_policy" rows="2" class="form-control" placeholder="e.g. Full refund up to 7 days before departure...">{{ old('cancellation_policy', 'Full refund up to 7 days before departure. 50% refund up to 72 hours prior.') }}</textarea>
                </div>

                <!-- Switches -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_published" value="1" class="custom-control-input" id="isPublishedCheck" checked>
                            <label class="custom-control-label" for="isPublishedCheck">Publish Tour (Visible to customers)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_featured" value="1" class="custom-control-input" id="isFeaturedCheck">
                            <label class="custom-control-label" for="isFeaturedCheck">Mark as Featured Expedition</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.tours.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background-color: #6D1A95; border-color: #6D1A95;">
                        <i class="fas fa-save me-1"></i> Save &amp; Create Tour
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        let dayCounter = 1;
        document.getElementById('btnAddItinerary').addEventListener('click', function() {
            dayCounter++;
            const container = document.getElementById('itineraryContainer');
            const row = document.createElement('div');
            row.className = 'itinerary-row border rounded p-3 mb-3 bg-light';
            row.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="font-weight-600 mb-0">Day ${dayCounter} Title</label>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.itinerary-row').remove();">Remove</button>
                </div>
                <input type="text" name="itinerary_titles[]" class="form-control mb-2" placeholder="e.g. Wet Mill Processing & Field Cupping">
                <label>Day ${dayCounter} Activities Description</label>
                <textarea name="itinerary_descriptions[]" rows="2" class="form-control" placeholder="Details of the day's events..."></textarea>
            `;
            container.appendChild(row);
        });
    </script>
    @endpush
@endsection
