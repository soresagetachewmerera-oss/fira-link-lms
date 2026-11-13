@extends('layouts.app')

@section('breadcrumb')
    @php
        $page_title = $page->title;
        $base_url = route('home');
    @endphp
    @include('partials.breadcrumb')
@endsection

@section('content')
    <section class="mhq-about-section section-padding custom-page-hero">
        <div class="container">
            <div class="custom-page-header text-center mb-5">
                <span class="badge badge-success-soft">{{ $page->title }}</span>
                @if ($page->description)
                    <p class="lead text-muted mt-3">{{ $page->description }}</p>
                @endif
            </div>
            <div class="custom-page-content">
                {!! $page->content !!}
            </div>
        </div>
    </section>
@endsection

@push('meta')
    @if (!empty($seoMeta['title']))
        <title>{{ $seoMeta['title'] }}</title>
    @endif
    @if (!empty($seoMeta['description']))
        <meta name="description" content="{{ $seoMeta['description'] }}">
    @endif
@endpush
