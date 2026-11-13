<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method ?? false)
        @method($method)
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Menu Details</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $menu->name) }}"
                            class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="slug">Slug</label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $menu->slug) }}"
                                class="form-control @error('slug') is-invalid @enderror"
                                placeholder="Auto-generated if left blank">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="location">Location <span class="text-danger">*</span></label>
                            <input list="menu-locations" type="text" id="location" name="location"
                                value="{{ old('location', $menu->location) }}"
                                class="form-control @error('location') is-invalid @enderror" required>
                            <datalist id="menu-locations">
                                @foreach ($locationSuggestions as $suggestion)
                                    <option value="{{ $suggestion }}"></option>
                                @endforeach
                            </datalist>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="form-control @error('description') is-invalid @enderror">{{ old('description', $menu->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Status</h4>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                            value="1" {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Menu is active</label>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('admin.frontend.menus.index') }}" class="btn btn-light mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</form>
