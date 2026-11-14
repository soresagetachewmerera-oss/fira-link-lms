@php
    $t = fn ($key, $r = []) => __('verification.' . $key, $r);
    $fmt = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->translatedFormat('j M Y') : '';
    $pageTitle = $profile ? $profile['name'] . ' · ' . $t('public.fira_id') . ' ' . $profile['fira_id'] : $t('public.page_title');
    $metaDescription = $profile
        ? $t('public.share_description', ['name' => $profile['name'], 'role' => $t('roles.' . $profile['business_role'])])
        : $t('public.heading');
    $isPrivate = true; // noindex: opens from shared links, never listed in search engines
    $ogType = 'profile';
@endphp
@extends('layouts.app')

@push('styles')
<style>
    .fv-wrap { max-width: 640px; margin: 0 auto; padding: var(--fira-space-4) var(--fira-space-2); font-family: var(--fira-font); color: var(--fira-neutral-900); }
    .fv-card { background: var(--fira-white); border: 1px solid var(--fira-neutral-300); border-radius: var(--fira-radius); padding: var(--fira-space-3); margin-bottom: var(--fira-space-2); }
    .fv-muted { color: var(--fira-neutral-500); font-size: 14px; }
    .fv-head { display: flex; gap: var(--fira-space-2); align-items: flex-start; justify-content: space-between; flex-wrap: wrap; }
    .fv-badge { display: inline-block; padding: 4px 12px; border-radius: var(--fira-radius); font-weight: 600; font-size: 14px; background: var(--fira-neutral-100); color: var(--fira-neutral-700); }
    .fv-badge.is-verified { background: var(--fira-primary-tint); color: var(--fira-primary); }
    .fv-qr { width: 120px; height: 120px; }
    .fv-list { list-style: none; padding: 0; margin: 0; }
    .fv-list li { padding: var(--fira-space-1) 0; border-bottom: 1px solid var(--fira-neutral-100); }
    .fv-list li:last-child { border-bottom: 0; }
    .fv-stats { display: flex; gap: var(--fira-space-3); flex-wrap: wrap; }
    .fv-stat strong { display: block; font-size: 20px; }
    .fv-actions { display: flex; gap: var(--fira-space-1); flex-wrap: wrap; }
    .fv-btn { display: inline-block; padding: 10px 16px; border-radius: var(--fira-radius); border: 1px solid var(--fira-primary); color: var(--fira-primary); text-decoration: none; font-weight: 600; }
    .fv-btn.is-primary { background: var(--fira-primary); color: var(--fira-white); }
    .fv-input { width: 100%; padding: 10px 12px; border: 1px solid var(--fira-neutral-300); border-radius: var(--fira-radius); font-size: 16px; }
    .fv-lang a { color: var(--fira-primary); margin-left: var(--fira-space-1); }
    .fv-notice { background: var(--fira-primary-tint); }
</style>
@endpush

@section('content')
<div class="fv-wrap">
    <div class="fv-head" style="margin-bottom: var(--fira-space-2);">
        <div>
            <div class="fv-muted">{{ $t('public.page_title') }}</div>
            <h1 style="font-size: 22px; margin: 0;">{{ $t('public.heading') }}</h1>
        </div>
        <div class="fv-lang fv-muted">
            <a href="{{ request()->url() }}?lang=en" hreflang="en">English</a>
            <a href="{{ request()->url() }}?lang=am" hreflang="am">አማርኛ</a>
        </div>
    </div>

    @if(!$profile)
        <div class="fv-card">
            @if($query !== null)
                <p role="alert"><strong>{{ $t('public.not_found') }}</strong></p>
            @endif
            <form method="GET" action="{{ route('verify.index') }}">
                <label for="fira-id" class="fv-muted">{{ $t('public.enter_id') }}</label>
                <input id="fira-id" name="id" class="fv-input" placeholder="{{ $t('public.enter_id_hint') }}" autocomplete="off" maxlength="20" value="{{ is_string($query) ? $query : '' }}">
                <div style="margin-top: var(--fira-space-2);"><button type="submit" class="fv-btn is-primary">{{ $t('public.check') }}</button></div>
            </form>
        </div>
    @else
        <div class="fv-card">
            <div class="fv-head">
                <div>
                    <h2 style="font-size: 20px; margin: 0 0 4px;">{{ $profile['name'] }}</h2>
                    <div class="fv-muted">
                        {{ $t('roles.' . $profile['business_role']) }}@if($profile['city']) · {{ $profile['city'] }}@endif · {{ $profile['country'] }}
                    </div>
                    <div class="fv-muted" style="margin-top: 4px;">{{ $t('public.fira_id') }} <strong>{{ $profile['fira_id'] }}</strong></div>
                    <div style="margin-top: var(--fira-space-2);">
                        @if($profile['status'] === 'verified')
                            <span class="fv-badge is-verified">{{ $t('public.verified_tier', ['tier' => $profile['tier']]) }}</span>
                            <div class="fv-muted" style="margin-top: 6px;">
                                {{ $t('public.reviewed_on', ['date' => $fmt($profile['reviewed_at'])]) }}
                                @if($profile['valid_until']) · {{ $t('public.valid_until', ['date' => $fmt($profile['valid_until'])]) }}@endif
                            </div>
                        @elseif($profile['status'] === 'suspended')
                            <span class="fv-badge">{{ $t('public.suspended') }}</span>
                        @else
                            <span class="fv-badge">{{ $t('public.expired') }}</span>
                        @endif
                    </div>
                </div>
                <img class="fv-qr" src="{{ $profile['qr_svg_url'] }}" alt="{{ $t('public.fira_id') }} {{ $profile['fira_id'] }}">
            </div>
        </div>

        @if($profile['status'] === 'verified')
            <div class="fv-card">
                <h3 style="font-size: 16px;">{{ $t('public.what_we_checked') }}</h3>
                <ul class="fv-list">
                    @foreach($profile['checked'] as $item)
                        <li>
                            <strong>{{ $item['check'] ? $t('checks.' . $item['check']) : $t('doc_types.' . $item['document_type']) }}</strong>
                            <div class="fv-muted">
                                {{ $t('public.checked_on', ['date' => $fmt($item['checked_at'])]) }}
                                @if($item['valid_until']) · {{ $t('public.valid_until', ['date' => $fmt($item['valid_until'])]) }}@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if($profile['vetted'])
                    <p class="fv-muted" style="margin-top: var(--fira-space-2);">{{ $t('public.vetted') }}</p>
                @endif
            </div>

            <div class="fv-card">
                <h3 style="font-size: 16px;">{{ $t('public.what_we_did_not_check') }}</h3>
                <p>{{ $t('public.not_checked_text') }}</p>
                <p class="fv-muted">{{ $t('public.meaning', ['date' => $fmt($profile['reviewed_at'])]) }}</p>
            </div>
        @endif

        <div class="fv-card fv-stats">
            <div class="fv-stat">
                @if($profile['confirmed_deals'] > 0)
                    <strong>{{ $profile['confirmed_deals'] }}</strong>
                    <span class="fv-muted">{{ $t('public.confirmed_deals') }}</span>
                @else
                    <span class="fv-muted">{{ $t('public.no_deals') }}</span>
                @endif
            </div>
            <div class="fv-stat">
                <strong>{{ $profile['member_since'] }}</strong>
                <span class="fv-muted">{{ $t('public.member_since') }}</span>
            </div>
        </div>

        <div class="fv-card fv-notice">
            <strong>{{ $t('public.never_pay_outside') }}</strong>
            <p class="fv-muted" style="margin: 4px 0 0;">{{ $t('public.never_ask_money') }}</p>
        </div>

        <div class="fv-actions">
            <a class="fv-btn is-primary" href="{{ route('login') }}">{{ $t('public.open_in_app') }}</a>
            <a class="fv-btn" href="{{ route('login') }}">{{ $t('public.report') }}</a>
        </div>
    @endif
</div>
@endsection
