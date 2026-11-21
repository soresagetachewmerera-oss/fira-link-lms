@props(['url', 'fallbackLabel' => ''])
@php($c = config('mail_brand.colors'))
@php($r = config('mail_brand.radius.button'))
@php($font = config('mail_brand.font'))
{{-- Bulletproof button: a padded cell with a real link (48 px tall), then the plain link for clients that block it. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 12px 0;">
  <tr>
    <td align="center" bgcolor="{{ $c['purple'] }}" style="border-radius:{{ $r }}px;background-color:{{ $c['purple'] }};">
      <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:14px 28px;font-family:{{ $font }};font-size:16px;line-height:20px;font-weight:600;color:{{ $c['white'] }};text-decoration:none;border-radius:{{ $r }}px;">{{ $slot }}</a>
    </td>
  </tr>
</table>
@if ($fallbackLabel)
<p style="margin:0 0 16px 0;font-family:{{ $font }};font-size:13px;line-height:20px;color:{{ $c['muted'] }};">{{ $fallbackLabel }}<br><a href="{{ $url }}" style="color:{{ $c['purple'] }};text-decoration:underline;word-break:break-all;">{{ $url }}</a></p>
@endif
