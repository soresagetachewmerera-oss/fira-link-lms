@php
    $socialLinks = collect(frontend_setting('footer.social_links', []))
        ->filter(fn($link) => filled($link['url'] ?? null))
        ->values();
@endphp

@if ($socialLinks->isNotEmpty())
<!-- Header Top Section Start -->
<div class="header-top-section">
    <div class="container-fluid">
        <div class="header-top-wrapper justify-content-end">
            <div class="header-top-right d-flex align-items-center">
                <div class="social-icon">
                    @foreach ($socialLinks as $link)
                        @php
                            $label = $link['label'] ?? null;
                            $icon = $link['icon'] ?? 'fab fa-globe';
                        @endphp
                        <a href="{{ $link['url'] }}"
                            @if ($label) aria-label="{{ $label }}" @endif target="_blank"
                            rel="noopener">
                            <i class="{{ $icon }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .header-top-section {
        overflow: visible !important;
        position: relative;
        z-index: 1020;
    }
    
    .header-top-wrapper {
        overflow: visible !important;
    }
</style>
@endpush
