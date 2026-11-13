@extends('layouts.admin')

@section('title', 'FAQ Content')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Frontend Manager</a></div>
    <div class="breadcrumb-item">FAQ Content</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Frequently Asked Questions</h4>

                </div>

                <form method="POST" action="{{ route('admin.frontend.faq.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Manage the FAQ items displayed on the public FAQ page. Use the editor to provide concise answers
                            with optional formatting.
                        </p>

                        <div id="faq-items-wrapper">
                            @forelse($faqItems as $index => $item)
                                @include('admin.frontend.settings.partials.faq-item', [
                                    'index' => $index,
                                    'item' => $item,
                                ])
                            @empty
                                @include('admin.frontend.settings.partials.faq-item', [
                                    'index' => 0,
                                    'item' => ['question' => '', 'answer' => ''],
                                ])
                            @endforelse
                        </div>

                        <div class="text-right mt-3">
                            <button type="button" class="btn btn-outline-primary" id="add-faq-item"
                                data-max="{{ $maxFaqItems }}">
                                <i class="fas fa-plus mr-1"></i> Add Item
                            </button>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <template id="faq-item-template">
        @include('admin.frontend.settings.partials.faq-item', [
            'index' => '__INDEX__',
            'item' => ['question' => '', 'answer' => ''],
        ])
    </template>
@endsection

@push('scripts')
    <script>
        (function($) {
            'use strict';

            const $wrapper = $('#faq-items-wrapper');
            const $template = $('#faq-item-template');
            const summernoteConfig = {
                height: 220,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            };

            const initializeEditors = (context) => {
                context.find('.faq-answer-editor').summernote(summernoteConfig);
            };

            initializeEditors($wrapper);

            $('#add-faq-item').on('click', function() {
                const maxItems = parseInt($(this).data('max'), 10);
                const currentCount = $wrapper.find('.faq-item-card').length;

                if (currentCount >= maxItems) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit reached',
                        text: 'You have reached the maximum number of FAQ items.'
                    });
                    return;
                }

                const nextIndex = Date.now();
                let html = $template.html();
                html = html.replace(/__INDEX__/g, nextIndex);

                const $element = $(html);
                $wrapper.append($element);
                initializeEditors($element);
            });

            $wrapper.on('click', '.remove-faq-item', function() {
                const $card = $(this).closest('.faq-item-card');
                const total = $wrapper.find('.faq-item-card').length;

                if (total <= 1) {
                    Swal.fire({
                        icon: 'info',
                        title: 'At least one item required',
                        text: 'Please keep at least one FAQ item.'
                    });
                    return;
                }

                const summernote = $card.find('.faq-answer-editor');
                if (summernote.length) {
                    summernote.summernote('destroy');
                }

                $card.remove();
            });
        })(jQuery);
    </script>
@endpush
