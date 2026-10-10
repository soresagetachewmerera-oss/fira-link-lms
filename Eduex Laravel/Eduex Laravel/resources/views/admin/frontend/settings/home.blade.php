@extends('layouts.admin')

@section('title', 'Homepage Settings')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Frontend Manager</a></div>
    <div class="breadcrumb-item">Homepage</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <form action="{{ route('admin.frontend.home.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Hero Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="hero_preheading">Preheading</label>
                                <input type="text" id="hero_preheading" name="hero[preheading]" class="form-control"
                                    value="{{ old('hero.preheading', $hero['preheading'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="hero_heading">Heading</label>
                                <input type="text" id="hero_heading" name="hero[heading]" class="form-control"
                                    value="{{ old('hero.heading', $hero['heading'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="hero_description">Description</label>
                            <textarea id="hero_description" name="hero[description]" class="form-control" rows="3">{{ old('hero.description', $hero['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="hero_cta_label">CTA Label</label>
                                <input type="text" id="hero_cta_label" name="hero[cta_label]" class="form-control"
                                    value="{{ old('hero.cta_label', $hero['cta_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="hero_cta_url">CTA URL</label>
                                <input type="text" id="hero_cta_url" name="hero[cta_url]" class="form-control"
                                    value="{{ old('hero.cta_url', $hero['cta_url'] ?? '') }}">
                                <small class="form-text text-muted">You can use route helpers like
                                    {{ route('courses') }}.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Testimonial Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="testimonial_title">Title</label>
                            <input type="text" id="testimonial_title" name="testimonial[title]" class="form-control"
                                value="{{ old('testimonial.title', $testimonial['title'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label for="testimonial_subtitle">Subtitle</label>
                            <textarea id="testimonial_subtitle" name="testimonial[subtitle]" class="form-control" rows="2">{{ old('testimonial.subtitle', $testimonial['subtitle'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="testimonial_cta_label">CTA Label</label>
                                <input type="text" id="testimonial_cta_label" name="testimonial[cta_label]"
                                    class="form-control"
                                    value="{{ old('testimonial.cta_label', $testimonial['cta_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="testimonial_cta_url">CTA URL</label>
                                <input type="text" id="testimonial_cta_url" name="testimonial[cta_url]"
                                    class="form-control"
                                    value="{{ old('testimonial.cta_url', $testimonial['cta_url'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Blog Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="blog_title">Title</label>
                            <input type="text" id="blog_title" name="blog[title]" class="form-control"
                                value="{{ old('blog.title', $blog['title'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label for="blog_subtitle">Subtitle</label>
                            <input type="text" id="blog_subtitle" name="blog[subtitle]" class="form-control"
                                value="{{ old('blog.subtitle', $blog['subtitle'] ?? '') }}">
                        </div>
                        <div class="form-group mb-0">
                            <label for="blog_description">Short Description</label>
                            <textarea id="blog_description" name="blog[description]" class="form-control" rows="3">{{ old('blog.description', $blog['description'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">About Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="about_subheading">Subheading</label>
                                <input type="text" id="about_subheading" name="about[subheading]"
                                    class="form-control"
                                    value="{{ old('about.subheading', $about['subheading'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="about_heading">Heading</label>
                                <input type="text" id="about_heading" name="about[heading]" class="form-control"
                                    value="{{ old('about.heading', $about['heading'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="about_description">Description</label>
                            <textarea id="about_description" name="about[description]" class="form-control" rows="3">{{ old('about.description', $about['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="about_cta_label">CTA Label</label>
                                <input type="text" id="about_cta_label" name="about[cta_label]" class="form-control"
                                    value="{{ old('about.cta_label', $about['cta_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="about_cta_url">CTA URL</label>
                                <input type="text" id="about_cta_url" name="about[cta_url]" class="form-control"
                                    value="{{ old('about.cta_url', $about['cta_url'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-row mb-0">
                            <div class="form-group col-md-6">
                                <label for="about_author_name">Author Name</label>
                                <input type="text" id="about_author_name" name="about[author_name]"
                                    class="form-control"
                                    value="{{ old('about.author_name', $about['author_name'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="about_author_title">Author Title</label>
                                <input type="text" id="about_author_title" name="about[author_title]"
                                    class="form-control"
                                    value="{{ old('about.author_title', $about['author_title'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Discount Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="discount_heading">Heading</label>
                            <input type="text" id="discount_heading" name="discount[heading]" class="form-control"
                                value="{{ old('discount.heading', $discount['heading'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label for="discount_description">Description</label>
                            <textarea id="discount_description" name="discount[description]" class="form-control" rows="3">{{ old('discount.description', $discount['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="discount_primary_label">Primary CTA Label</label>
                                <input type="text" id="discount_primary_label" name="discount[primary_label]"
                                    class="form-control"
                                    value="{{ old('discount.primary_label', $discount['primary_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="discount_primary_url">Primary CTA URL</label>
                                <input type="text" id="discount_primary_url" name="discount[primary_url]"
                                    class="form-control"
                                    value="{{ old('discount.primary_url', $discount['primary_url'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-row mb-0">
                            <div class="form-group col-md-6">
                                <label for="discount_secondary_label">Secondary CTA Label</label>
                                <input type="text" id="discount_secondary_label" name="discount[secondary_label]"
                                    class="form-control"
                                    value="{{ old('discount.secondary_label', $discount['secondary_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="discount_secondary_url">Secondary CTA URL</label>
                                <input type="text" id="discount_secondary_url" name="discount[secondary_url]"
                                    class="form-control"
                                    value="{{ old('discount.secondary_url', $discount['secondary_url'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Why Choose Us Section</h4>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-feature-item"><i
                                class="fas fa-plus mr-1"></i> Add Feature</button>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="features_title">Title</label>
                            <input type="text" id="features_title" name="features[title]" class="form-control"
                                value="{{ old('features.title', $features['title'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label for="features_subtitle">Subtitle</label>
                            <input type="text" id="features_subtitle" name="features[subtitle]" class="form-control"
                                value="{{ old('features.subtitle', $features['subtitle'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label for="features_description">Description</label>
                            <textarea id="features_description" name="features[description]" class="form-control" rows="3">{{ old('features.description', $features['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="features_highlight_count">Highlight Count</label>
                                <input type="number" id="features_highlight_count" name="features[highlight_count]"
                                    class="form-control"
                                    value="{{ old('features.highlight_count', $features['highlight_count'] ?? 0) }}">
                            </div>
                            <div class="form-group col-md-8">
                                <label for="features_highlight_label">Highlight Label</label>
                                <input type="text" id="features_highlight_label" name="features[highlight_label]"
                                    class="form-control"
                                    value="{{ old('features.highlight_label', $features['highlight_label'] ?? '') }}">
                            </div>
                        </div>

                        @php($featureItems = old('features.items', $features['items'] ?? []))
                        <div id="feature-items-wrapper">
                            @forelse($featureItems as $index => $item)
                                <div class="card feature-item-card mb-3">
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Icon Class</label>
                                                <input type="text" name="features[items][{{ $index }}][icon]"
                                                    class="form-control" value="{{ $item['icon'] ?? '' }}"
                                                    placeholder="fa-solid fa-star">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Title</label>
                                                <input type="text" name="features[items][{{ $index }}][title]"
                                                    class="form-control" value="{{ $item['title'] ?? '' }}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Description</label>
                                                <input type="text"
                                                    name="features[items][{{ $index }}][description]"
                                                    class="form-control" value="{{ $item['description'] ?? '' }}">
                                            </div>
                                        </div>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger remove-feature-item"><i
                                                class="fas fa-trash"></i> Remove</button>
                                    </div>
                                </div>
                            @empty
                                <div class="card feature-item-card mb-3">
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Icon Class</label>
                                                <input type="text" name="features[items][0][icon]"
                                                    class="form-control" placeholder="fa-solid fa-star">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Title</label>
                                                <input type="text" name="features[items][0][title]"
                                                    class="form-control">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Description</label>
                                                <input type="text" name="features[items][0][description]"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger remove-feature-item"><i
                                                class="fas fa-trash"></i> Remove</button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Newsletter CTA</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="cta_heading">Heading</label>
                            <input type="text" id="cta_heading" name="cta[heading]" class="form-control"
                                value="{{ old('cta.heading', $cta['heading'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label for="cta_description">Description</label>
                            <textarea id="cta_description" name="cta[description]" class="form-control" rows="2">{{ old('cta.description', $cta['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="cta_placeholder">Input Placeholder</label>
                                <input type="text" id="cta_placeholder" name="cta[placeholder]" class="form-control"
                                    value="{{ old('cta.placeholder', $cta['placeholder'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="cta_button_label">Button Label</label>
                                <input type="text" id="cta_button_label" name="cta[button_label]"
                                    class="form-control"
                                    value="{{ old('cta.button_label', $cta['button_label'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Homepage</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function($) {
            'use strict';

            const $wrapper = $('#feature-items-wrapper');
            let nextIndex = $wrapper.find('.feature-item-card').length;

            $('#add-feature-item').on('click', function() {
                const template = `
                <div class="card feature-item-card mb-3">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Icon Class</label>
                                <input type="text" name="features[items][${nextIndex}][icon]" class="form-control" placeholder="fa-solid fa-star">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Title</label>
                                <input type="text" name="features[items][${nextIndex}][title]" class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Description</label>
                                <input type="text" name="features[items][${nextIndex}][description]" class="form-control">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-feature-item"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                </div>`;
                $wrapper.append(template);
                nextIndex++;
            });

            $wrapper.on('click', '.remove-feature-item', function() {
                if ($wrapper.find('.feature-item-card').length <= 1) {
                    Swal.fire({
                        icon: 'info',
                        title: 'At least one feature',
                        text: 'Please keep at least one feature item.',
                    });
                    return;
                }
                $(this).closest('.feature-item-card').remove();
            });
        })(jQuery);
    </script>
@endpush
