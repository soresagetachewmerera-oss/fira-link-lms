<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Post Details</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title"
                            class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title', $blog->title) }}" required data-slug-source>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug"
                            class="form-control @error('slug') is-invalid @enderror"
                            value="{{ old('slug', $blog->slug) }}" data-slug-target>
                        <small class="form-text text-muted">Leave blank to generate automatically.</small>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="blog_category_id">Category</label>
                            <select id="blog_category_id" name="blog_category_id"
                                class="form-control @error('blog_category_id') is-invalid @enderror">
                                <option value="">Select category</option>
                                @foreach ($categoryOptions as $option)
                                    <option value="{{ $option->id }}"
                                        {{ (string) $formDefaults['selectedCategoryId'] === (string) $option->id ? 'selected' : '' }}>
                                        {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($formDefaults['selectedCategoryLabel'] && !$formDefaults['selectedCategoryId'])
                                <small class="text-muted d-block mt-1">Current:
                                    {{ $formDefaults['selectedCategoryLabel'] }}</small>
                            @endif
                            @if ($categoryOptions->isEmpty())
                                <small class="text-muted d-block mt-1">No categories available yet. Please create one
                                    from the admin panel.</small>
                            @endif
                            <input type="hidden" name="category_label" id="category_label"
                                value="{{ $formDefaults['selectedCategoryLabel'] }}">
                            @error('blog_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="author_name">Author Name</label>
                            <input type="text" id="author_name" name="author_name"
                                class="form-control @error('author_name') is-invalid @enderror"
                                value="{{ old('author_name', $blog->author_name) }}">
                            @error('author_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $blog->excerpt) }}</textarea>
                        <small class="form-text text-muted">Short summary displayed on listing pages (max 600
                            characters).</small>
                        @error('excerpt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="content">Content <span class="text-danger">*</span></label>
                        <textarea id="content" name="content" class="summernote @error('content') is-invalid @enderror">{{ old('content', $blog->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Publish Settings</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_published" name="is_published"
                                value="1" {{ $formDefaults['is_published'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_published">Publish immediately</label>
                        </div>
                        <small class="form-text text-muted">Disable to keep as draft.</small>
                    </div>

                    <div class="form-group">
                        <label for="published_at">Publish at</label>
                        <input type="datetime-local" id="published_at" name="published_at"
                            class="form-control @error('published_at') is-invalid @enderror"
                            value="{{ $formDefaults['published_at'] }}">
                        @error('published_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="reading_time_minutes">Estimated reading time (minutes)</label>
                        <input type="number" min="1" max="240" id="reading_time_minutes"
                            name="reading_time_minutes"
                            class="form-control @error('reading_time_minutes') is-invalid @enderror"
                            value="{{ $formDefaults['reading_time_minutes'] }}">
                        @error('reading_time_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Featured Image</h4>
                </div>
                <div class="card-body">
                    @if ($formDefaults['featured_image_url'])
                        <div class="mb-3 text-center">
                            <img src="{{ $formDefaults['featured_image_url'] }}" alt="Featured image"
                                class="img-fluid rounded">
                        </div>
                    @endif
                    <div class="form-group mb-0">
                        <label for="featured_image">Upload image</label>
                        <input type="file" id="featured_image" name="featured_image"
                            class="form-control-file @error('featured_image') is-invalid @enderror" accept="image/*">
                        <small class="form-text text-muted">Supported formats: JPG, PNG, GIF, WEBP. Max size
                            5MB.</small>
                        @error('featured_image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Tags</h4>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="tags">Comma separated</label>
                        <input type="text" id="tags" name="tags"
                            class="form-control @error('tags') is-invalid @enderror"
                            value="{{ $formDefaults['tag_string'] }}">
                        <small class="form-text text-muted">Example: education, product, agency</small>
                        @error('tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-light mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</form>
