<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method)
        @method($method)
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Category Details</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $category->name) }}" required data-slug-source>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="slug">Slug</label>
                            <input type="text" id="slug" name="slug"
                                class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug', $category->slug) }}" placeholder="auto-generated if blank"
                                data-slug-target>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color"
                                class="form-control @error('color') is-invalid @enderror"
                                value="{{ old('color', $formDefaults['color']) }}" placeholder="#F05A28 or theme class">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                            class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
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
                    <h4>Visibility</h4>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                            value="1" {{ $formDefaults['is_active'] ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Display Settings</h4>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" min="0"
                            class="form-control @error('display_order') is-invalid @enderror"
                            value="{{ $formDefaults['display_order'] }}">
                        <small class="form-text text-muted">Lower numbers appear first.</small>
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('admin.blog-categories.index') }}" class="btn btn-light mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</form>
