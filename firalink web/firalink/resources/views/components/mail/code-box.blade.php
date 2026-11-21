@props(['code', 'label' => ''])
@php($c = config('mail_brand.colors'))
@php($font = config('mail_brand.font'))
{{-- The only place a code appears in an email: large, spaced, on tint, easy to copy. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px 0;">
  <tr>
    <td align="center" style="padding:20px 16px;background-color:{{ $c['tint'] }};border-radius:{{ config('mail_brand.radius.box') }}px;">
      @if ($label)
      <p style="margin:0 0 8px 0;font-family:{{ $font }};font-size:13px;line-height:20px;color:{{ $c['deep'] }};">{{ $label }}</p>
      @endif
      <p style="margin:0;font-family:'Courier New', Courier, {{ $font }};font-size:32px;line-height:40px;font-weight:700;letter-spacing:8px;color:{{ $c['text'] }};-webkit-user-select:all;user-select:all;">{{ $code }}</p>
    </td>
  </tr>
</table>
