@php($c = config('mail_brand.colors'))
<p style="margin:0 0 8px 0;font-family:{{ config('mail_brand.font') }};font-size:13px;line-height:20px;font-weight:600;color:{{ $c['purple'] }};">{{ $slot }}</p>
