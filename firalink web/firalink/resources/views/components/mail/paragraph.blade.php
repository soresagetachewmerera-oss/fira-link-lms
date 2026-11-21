@props(['muted' => false])
@php($c = config('mail_brand.colors'))
<p style="margin:0 0 16px 0;font-family:{{ config('mail_brand.font') }};font-size:{{ $muted ? 14 : 16 }}px;line-height:{{ $muted ? 22 : 26 }}px;color:{{ $muted ? $c['muted'] : $c['text'] }};">{{ $slot }}</p>
