@php
    $contact = frontend_setting('footer.contact', []);
    $socialLinks = collect(frontend_setting('footer.social_links', []))
        ->filter(fn($link) => filled($link['url'] ?? null))
        ->values();

    $phone = $contact['phone'] ?? null;
    $phoneLink = $phone ? preg_replace('/[^\d\+]/', '', $phone) : null;
    $email = $contact['email'] ?? null;
    $address = $contact['address'] ?? null;
@endphp

<!-- Header Top Section Start -->
<div class="header-top-section">
    <div class="container-fluid">
        <div class="header-top-wrapper">
            <ul>
                @if ($phone)
                    <li>
                        <i class="fa-solid fa-phone-plus"></i>
                        <a href="{{ $phoneLink ? 'tel:' . $phoneLink : '#' }}">{{ $phone }}</a>
                    </li>
                @endif

                @if ($email)
                    <li>
                        <i class="fa-solid fa-envelopes"></i>
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </li>
                @endif

                @if ($address)
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $address }}
                    </li>
                @endif
            </ul>

            <div class="header-top-right d-flex align-items-center">
                @if ($socialLinks->isNotEmpty())
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
                @endif
            </div>
        </div>
    </div>
</div>

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
