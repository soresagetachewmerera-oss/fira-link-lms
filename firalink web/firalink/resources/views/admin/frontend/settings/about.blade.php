@extends('layouts.admin')

@section('title', 'About Page Settings')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Frontend Manager</a></div>
    <div class="breadcrumb-item">About Page</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <form action="{{ route('admin.frontend.about.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Hero Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Subheading</label>
                                <input type="text" name="hero[subheading]" class="form-control"
                                    value="{{ old('hero.subheading', $hero['subheading'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Heading</label>
                                <input type="text" name="hero[heading]" class="form-control"
                                    value="{{ old('hero.heading', $hero['heading'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="hero[description]" class="form-control" rows="3">{{ old('hero.description', $hero['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>CTA Label</label>
                                <input type="text" name="hero[cta_label]" class="form-control"
                                    value="{{ old('hero.cta_label', $hero['cta_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>CTA URL</label>
                                <input type="text" name="hero[cta_url]" class="form-control"
                                    value="{{ old('hero.cta_url', $hero['cta_url'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-row mb-0">
                            <div class="form-group col-md-6">
                                <label>Author Name</label>
                                <input type="text" name="hero[author_name]" class="form-control"
                                    value="{{ old('hero.author_name', $hero['author_name'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Author Title</label>
                                <input type="text" name="hero[author_title]" class="form-control"
                                    value="{{ old('hero.author_title', $hero['author_title'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Why Choose Us Features</h4>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-about-feature">
                            <i class="fas fa-plus mr-1"></i> Add Feature
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="features[title]" class="form-control"
                                value="{{ old('features.title', $features['title'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Subtitle</label>
                            <input type="text" name="features[subtitle]" class="form-control"
                                value="{{ old('features.subtitle', $features['subtitle'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="features[description]" class="form-control" rows="3">{{ old('features.description', $features['description'] ?? '') }}</textarea>
                        </div>
                        @php($aboutFeatures = old('features.items', $features['items'] ?? []))
                        <div id="about-feature-wrapper">
                            @forelse($aboutFeatures as $index => $item)
                                <div class="card about-feature-card mb-3">
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Icon Class</label>
                                                <input type="text" name="features[items][{{ $index }}][icon]"
                                                    class="form-control" value="{{ $item['icon'] ?? '' }}"
                                                    placeholder="fa-solid fa-check">
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label>Text</label>
                                                <input type="text" name="features[items][{{ $index }}][text]"
                                                    class="form-control" value="{{ $item['text'] ?? '' }}">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-about-feature"><i
                                                class="fas fa-trash"></i> Remove</button>
                                    </div>
                                </div>
                            @empty
                                <div class="card about-feature-card mb-3">
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Icon Class</label>
                                                <input type="text" name="features[items][0][icon]" class="form-control"
                                                    placeholder="fa-solid fa-check">
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label>Text</label>
                                                <input type="text" name="features[items][0][text]"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger remove-about-feature"><i
                                                class="fas fa-trash"></i> Remove</button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Highlights Counters</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Configure the stats displayed alongside the "Why Choose Us" imagery.</p>
                        @php($highlightLeft = old('highlights.left', $highlights['left'] ?? []))
                        @php($highlightRight = old('highlights.right', $highlights['right'] ?? []))
                        <div class="form-row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Left Column</h6>
                                <div id="highlight-left-wrapper">
                                    @foreach ($highlightLeft ?: [[]] as $index => $item)
                                        <div class="card highlight-item-card mb-3">
                                            <div class="card-body">
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label>Icon</label>
                                                        <input type="text"
                                                            name="highlights[left][{{ $index }}][icon]"
                                                            class="form-control" value="{{ $item['icon'] ?? '' }}"
                                                            placeholder="fa-solid fa-star">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>Count</label>
                                                        <input type="text"
                                                            name="highlights[left][{{ $index }}][count]"
                                                            class="form-control" value="{{ $item['count'] ?? '' }}">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>Label</label>
                                                        <input type="text"
                                                            name="highlights[left][{{ $index }}][label]"
                                                            class="form-control" value="{{ $item['label'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger remove-highlight-item"><i
                                                        class="fas fa-trash"></i> Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    id="add-highlight-left"><i class="fas fa-plus mr-1"></i> Add</button>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Right Column</h6>
                                <div id="highlight-right-wrapper">
                                    @foreach ($highlightRight ?: [[]] as $index => $item)
                                        <div class="card highlight-item-card mb-3">
                                            <div class="card-body">
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label>Icon</label>
                                                        <input type="text"
                                                            name="highlights[right][{{ $index }}][icon]"
                                                            class="form-control" value="{{ $item['icon'] ?? '' }}"
                                                            placeholder="fa-solid fa-star">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>Count</label>
                                                        <input type="text"
                                                            name="highlights[right][{{ $index }}][count]"
                                                            class="form-control" value="{{ $item['count'] ?? '' }}">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>Label</label>
                                                        <input type="text"
                                                            name="highlights[right][{{ $index }}][label]"
                                                            class="form-control" value="{{ $item['label'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger remove-highlight-item"><i
                                                        class="fas fa-trash"></i> Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    id="add-highlight-right"><i class="fas fa-plus mr-1"></i> Add</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Call to Action</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Heading</label>
                            <input type="text" name="cta[heading]" class="form-control"
                                value="{{ old('cta.heading', $cta['heading'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="cta[description]" class="form-control" rows="2">{{ old('cta.description', $cta['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Primary CTA Label</label>
                                <input type="text" name="cta[primary_label]" class="form-control"
                                    value="{{ old('cta.primary_label', $cta['primary_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Primary CTA URL</label>
                                <input type="text" name="cta[primary_url]" class="form-control"
                                    value="{{ old('cta.primary_url', $cta['primary_url'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-row mb-0">
                            <div class="form-group col-md-6">
                                <label>Secondary CTA Label</label>
                                <input type="text" name="cta[secondary_label]" class="form-control"
                                    value="{{ old('cta.secondary_label', $cta['secondary_label'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Secondary CTA URL</label>
                                <input type="text" name="cta[secondary_url]" class="form-control"
                                    value="{{ old('cta.secondary_url', $cta['secondary_url'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save About Page</button>
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

            const featureWrapper = $('#about-feature-wrapper');
            let featureIndex = featureWrapper.find('.about-feature-card').length;

            $('#add-about-feature').on('click', function() {
                const template = `
                <div class="card about-feature-card mb-3">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Icon Class</label>
                                <input type="text" name="features[items][${featureIndex}][icon]" class="form-control" placeholder="fa-solid fa-check">
                            </div>
                            <div class="form-group col-md-8">
                                <label>Text</label>
                                <input type="text" name="features[items][${featureIndex}][text]" class="form-control">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-about-feature"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                </div>`;
                featureWrapper.append(template);
                featureIndex++;
            });

            featureWrapper.on('click', '.remove-about-feature', function() {
                if (featureWrapper.find('.about-feature-card').length <= 1) {
                    Swal.fire({
                        icon: 'info',
                        title: 'At least one feature',
                        text: 'Please keep at least one feature item.',
                    });
                    return;
                }
                $(this).closest('.about-feature-card').remove();
            });

            const makeHighlightTemplate = (column, index) => `
            <div class="card highlight-item-card mb-3">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Icon</label>
                            <input type="text" name="highlights[${column}][${index}][icon]" class="form-control" placeholder="fa-solid fa-star">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Count</label>
                            <input type="text" name="highlights[${column}][${index}][count]" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Label</label>
                            <input type="text" name="highlights[${column}][${index}][label]" class="form-control">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-highlight-item"><i class="fas fa-trash"></i> Remove</button>
                </div>
            </div>`;

            const leftWrapper = $('#highlight-left-wrapper');
            const rightWrapper = $('#highlight-right-wrapper');
            let leftIndex = leftWrapper.find('.highlight-item-card').length;
            let rightIndex = rightWrapper.find('.highlight-item-card').length;

            $('#add-highlight-left').on('click', function() {
                leftWrapper.append(makeHighlightTemplate('left', leftIndex++));
            });

            $('#add-highlight-right').on('click', function() {
                rightWrapper.append(makeHighlightTemplate('right', rightIndex++));
            });

            $('#highlight-left-wrapper, #highlight-right-wrapper').on('click', '.remove-highlight-item', function() {
                const $container = $(this).closest('#highlight-left-wrapper, #highlight-right-wrapper');
                if ($container.find('.highlight-item-card').length <= 1) {
                    Swal.fire({
                        icon: 'info',
                        title: 'At least one item required'
                    });
                    return;
                }
                $(this).closest('.highlight-item-card').remove();
            });
        })(jQuery);
    </script>
@endpush
