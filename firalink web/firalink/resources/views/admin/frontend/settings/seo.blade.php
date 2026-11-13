@extends('layouts.admin')

@section('title', 'SEO Metadata')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Frontend Manager</a></div>
    <div class="breadcrumb-item">SEO Metadata</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Core Pages</h4>
                </div>
                <form method="POST" action="{{ route('admin.frontend.seo.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Update the SEO title and description for core frontend pages. Fields are optional—leave blank to
                            use default values.
                        </p>

                        @foreach ($corePages as $slug => $label)
                            @php
                                $meta = $metaEntries[$slug] ?? ['title' => '', 'description' => ''];
                            @endphp
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">{{ $label }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="meta_{{ $slug }}_title">SEO Title</label>
                                        <input type="text" id="meta_{{ $slug }}_title"
                                            name="meta[{{ $slug }}][title]"
                                            class="form-control @error('meta.' . $slug . '.title') is-invalid @enderror"
                                            value="{{ old('meta.' . $slug . '.title', $meta['title'] ?? '') }}"
                                            maxlength="255"
                                            placeholder="Enter the page title shown in browsers and search results">
                                        @error('meta.' . $slug . '.title')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="meta_{{ $slug }}_description">Meta Description</label>
                                        <textarea id="meta_{{ $slug }}_description" name="meta[{{ $slug }}][description]"
                                            class="form-control @error('meta.' . $slug . '.description') is-invalid @enderror" rows="3" maxlength="255"
                                            placeholder="Summarize the page content for search engines">{{ old('meta.' . $slug . '.description', $meta['description'] ?? '') }}</textarea>
                                        @error('meta.' . $slug . '.description')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($customPages->isNotEmpty())
                        <div class="card-body border-top">
                            <h4 class="mb-3">Custom Pages</h4>
                            @foreach ($customPages as $customPage)
                                @php
                                    $slug = $customPage->slug;
                                    $meta = $metaEntries[$slug] ?? ['title' => '', 'description' => ''];
                                @endphp
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-file-alt text-primary mr-2"></i>{{ $customPage->title }}
                                        </h5>
                                        <span class="badge badge-light">{{ $slug }}</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="meta_{{ $slug }}_title">SEO Title</label>
                                            <input type="text" id="meta_{{ $slug }}_title"
                                                name="meta[{{ $slug }}][title]"
                                                class="form-control @error('meta.' . $slug . '.title') is-invalid @enderror"
                                                value="{{ old('meta.' . $slug . '.title', $meta['title'] ?? '') }}"
                                                maxlength="255"
                                                placeholder="Enter the page title shown in browsers and search results">
                                            @error('meta.' . $slug . '.title')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="meta_{{ $slug }}_description">Meta Description</label>
                                            <textarea id="meta_{{ $slug }}_description" name="meta[{{ $slug }}][description]"
                                                class="form-control @error('meta.' . $slug . '.description') is-invalid @enderror" rows="3" maxlength="255"
                                                placeholder="Summarize the page content for search engines">{{ old('meta.' . $slug . '.description', $meta['description'] ?? '') }}</textarea>
                                            @error('meta.' . $slug . '.description')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Metadata</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
