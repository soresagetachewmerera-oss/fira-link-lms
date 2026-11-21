@php($c = config('mail_brand.colors'))
{{-- Information or a warning on tint. The words carry the meaning, not the colour. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 20px 0;">
  <tr>
    <td style="padding:16px;background-color:{{ $c['tint'] }};border-radius:{{ config('mail_brand.radius.box') }}px;font-family:{{ config('mail_brand.font') }};font-size:14px;line-height:22px;color:{{ $c['text'] }};">{{ $slot }}</td>
  </tr>
</table>
