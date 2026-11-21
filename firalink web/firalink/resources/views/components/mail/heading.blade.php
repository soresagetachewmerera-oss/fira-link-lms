@php($c = config('mail_brand.colors'))
<h1 class="fl-h1" style="margin:0 0 16px 0;font-family:{{ config('mail_brand.font') }};font-size:24px;line-height:32px;font-weight:600;color:{{ $c['text'] }};">{{ $slot }}</h1>
