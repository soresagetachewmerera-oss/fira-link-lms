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

                {{-- Language Switcher Dropdown --}}
                @if (!empty($availableLocales) && count($availableLocales) > 1)
                    <div class="ms-4 position-relative d-flex align-items-center">
                        <select onchange="window.location.href=this.value" style="appearance: auto; -webkit-appearance: auto; background: transparent; border: 1px solid rgba(0,0,0,0.1); border-radius: 30px; padding: 4px 12px; color: var(--text); font-size: 13px; font-weight: bold; cursor: pointer; min-width: 120px;">
                            @foreach ($availableLocales as $code => $meta)
                                <option value="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" {{ $code === $currentLocale ? 'selected' : '' }} style="color: #333;">
                                    {{ $meta['flag'] }} {{ $meta['native'] }}
                                </option>
                            @endforeach
                        </select>
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
