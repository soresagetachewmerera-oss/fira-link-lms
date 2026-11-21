@props(['status' => 'pending'])
@php($c = config('mail_brand.colors'))
{{-- Word and colour together, never colour alone. Pending is purple on lavender. --}}
@php($style = match ($status) {
    'approved' => ['bg' => $c['purple'], 'fg' => $c['white'], 'border' => $c['purple']],
    'action_needed' => ['bg' => $c['white'], 'fg' => $c['deep'], 'border' => $c['purple']],
    default => ['bg' => $c['tint'], 'fg' => $c['purple'], 'border' => $c['tint']],
})
<span style="display:inline-block;margin:0 0 16px 0;padding:4px 12px;border:1px solid {{ $style['border'] }};border-radius:{{ config('mail_brand.radius.pill') }}px;background-color:{{ $style['bg'] }};font-family:{{ config('mail_brand.font') }};font-size:13px;line-height:20px;font-weight:600;color:{{ $style['fg'] }};">{{ $slot }}</span>
