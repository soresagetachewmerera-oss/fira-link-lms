@extends('layouts.admin')

@section('title', 'Contact Page')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Frontend Manager</a></div>
    <div class="breadcrumb-item">Contact Page</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <form method="POST" action="{{ route('admin.frontend.contact.update') }}">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Hero Section</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="hero-eyebrow">Eyebrow Text</label>
                                <input type="text" name="hero[eyebrow]" id="hero-eyebrow" class="form-control"
                                    value="{{ old('hero.eyebrow', $hero['eyebrow'] ?? '') }}" maxlength="120"
                                    placeholder="Need Any Help?">
                            </div>
                            <div class="form-group col-md-8">
                                <label for="hero-heading">Heading</label>
                                <input type="text" name="hero[heading]" id="hero-heading" class="form-control"
                                    value="{{ old('hero.heading', $hero['heading'] ?? '') }}" maxlength="180"
                                    placeholder="Get in touch with us">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="hero-description">Description</label>
                            <textarea name="hero[description]" id="hero-description" class="form-control" rows="4">{{ old('hero.description', $hero['description'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Contact Methods</h4>
                        <button type="button" class="btn btn-outline-primary" id="add-contact-method"
                            data-max="{{ $maxMethods }}">
                            <i class="fas fa-plus mr-1"></i> Add Method
                        </button>
                    </div>
                    <div class="card-body" id="contact-methods-wrapper">
                        @forelse($methods as $index => $method)
                            @include('admin.frontend.settings.partials.contact-method', [
                                'index' => $index,
                                'method' => $method,
                            ])
                        @empty
                            @include('admin.frontend.settings.partials.contact-method', [
                                'index' => 0,
                                'method' => [
                                    'icon' => 'fa-light fa-phone',
                                    'label' => 'Call us',
                                    'type' => 'phone',
                                    'value' => '',
                                    'link' => '',
                                ],
                            ])
                        @endforelse
                    </div>
                    <div class="card-footer text-muted">
                        Configure up to {{ $maxMethods }} contact options. Provide optional direct links for phone/email.
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Contact Form</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="form-heading">Form Heading</label>
                                <input type="text" name="form[heading]" id="form-heading" class="form-control"
                                    value="{{ old('form.heading', $form['heading'] ?? '') }}" maxlength="180"
                                    placeholder="Send us a message">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="form-submit-label">Submit Button Label</label>
                                <input type="text" name="form[submit_label]" id="form-submit-label" class="form-control"
                                    value="{{ old('form.submit_label', $form['submit_label'] ?? '') }}" maxlength="80"
                                    placeholder="Send Message">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="form-description">Form Helper Text</label>
                            <textarea name="form[description]" id="form-description" class="form-control" rows="3">{{ old('form.description', $form['description'] ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="form-success-message">Success Message</label>
                                <input type="text" name="form[success_message]" id="form-success-message" class="form-control"
                                    value="{{ old('form.success_message', $form['success_message'] ?? '') }}" maxlength="255"
                                    placeholder="Thank you for contacting {{ site_name() }}..."></input>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="form-subject-prefix">Email Subject Prefix</label>
                                <input type="text" name="form[subject_prefix]" id="form-subject-prefix" class="form-control"
                                    value="{{ old('form.subject_prefix', $form['subject_prefix'] ?? '') }}" maxlength="60"
                                    placeholder="[{{ site_name() }} Contact]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Map</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="map-embed">Google Maps Embed URL</label>
                            <textarea name="map[embed_url]" id="map-embed" class="form-control" rows="3" placeholder="https://www.google.com/maps/embed?pb=...">{{ old('map.embed_url', $map['embed_url'] ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="map-caption">Caption</label>
                            <input type="text" name="map[caption]" id="map-caption" class="form-control"
                                value="{{ old('map.caption', $map['caption'] ?? '') }}" maxlength="255"
                                placeholder="Our headquarters is located in the heart of the city.">
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Save Contact Page</button>
                </div>
            </form>
        </div>
    </div>

    <template id="contact-method-template">
        @include('admin.frontend.settings.partials.contact-method', [
            'index' => '__INDEX__',
            'method' => [
                'icon' => '',
                'label' => '',
                'type' => '',
                'value' => '',
                'link' => '',
            ],
        ])
    </template>
@endsection

@push('scripts')
    <script>
        (function($) {
            'use strict';

            const $wrapper = $('#contact-methods-wrapper');
            const $template = $('#contact-method-template');

            $('#add-contact-method').on('click', function() {
                const maxItems = parseInt($(this).data('max'), 10);
                const currentCount = $wrapper.find('.contact-method-card').length;

                if (currentCount >= maxItems) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit reached',
                        text: `You can only add up to ${maxItems} contact methods.`,
                    });
                    return;
                }

                const nextIndex = Date.now();
                let html = $template.html();
                html = html.replace(/__INDEX__/g, nextIndex);

                $wrapper.append(html);
            });

            $wrapper.on('click', '.remove-contact-method', function() {
                const $card = $(this).closest('.contact-method-card');
                if ($wrapper.find('.contact-method-card').length <= 1) {
                    Swal.fire({
                        icon: 'info',
                        title: 'At least one method required',
                        text: 'Please keep at least one contact method.',
                    });
                    return;
                }

                $card.remove();
            });
        })(jQuery);
    </script>
@endpush
