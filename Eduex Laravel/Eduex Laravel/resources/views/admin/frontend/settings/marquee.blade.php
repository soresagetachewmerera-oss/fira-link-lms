@extends('layouts.admin')

@section('title', 'Marquee Settings')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Frontend Manager</a></div>
    <div class="breadcrumb-item">Marquee</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Scrolling Marquee Items</h4>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-marquee-item"
                        data-max="{{ $maxItems }}">
                        <i class="fas fa-plus mr-1"></i> Add Item
                    </button>
                </div>
                <form action="{{ route('admin.frontend.marquee.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Configure the text and optional icon/image displayed in the marquee component. Items appear in
                            the order listed below.
                        </p>

                        @php($formItems = old('items', $items))

                        <div id="marquee-items-wrapper">
                            @forelse($formItems as $index => $item)
                                @include('admin.frontend.settings.partials.marquee-item', [
                                    'index' => $index,
                                    'item' => $item,
                                ])
                            @empty
                                @include('admin.frontend.settings.partials.marquee-item', [
                                    'index' => 0,
                                    'item' => ['image' => '', 'text' => ''],
                                ])
                            @endforelse
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Marquee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <template id="marquee-item-template">
        @include('admin.frontend.settings.partials.marquee-item', [
            'index' => '__INDEX__',
            'item' => ['image' => '', 'text' => ''],
        ])
    </template>
@endsection

@push('scripts')
    <script>
        (function($) {
            'use strict';

            const $wrapper = $('#marquee-items-wrapper');
            const $template = $('#marquee-item-template');

            $('#add-marquee-item').on('click', function() {
                const max = parseInt($(this).data('max'), 10);
                const total = $wrapper.find('.marquee-item-card').length;

                if (total >= max) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit reached',
                        text: 'You have reached the maximum number of marquee items.'
                    });
                    return;
                }

                const index = Date.now();
                let html = $template.html();
                html = html.replace(/__INDEX__/g, index);

                $wrapper.append($(html));
            });

            $wrapper.on('click', '.remove-marquee-item', function() {
                const $card = $(this).closest('.marquee-item-card');
                const total = $wrapper.find('.marquee-item-card').length;

                if (total <= 1) {
                    Swal.fire({
                        icon: 'info',
                        title: 'At least one item required',
                        text: 'Please keep at least one marquee item.'
                    });
                    return;
                }

                $card.remove();
            });
        })(jQuery);
    </script>
@endpush
