@props(['facts' => []])
@php($c = config('mail_brand.colors'))
@php($font = config('mail_brand.font'))
{{-- Label and value rows. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 20px 0;border-top:1px solid {{ $c['hairline'] }};">
  @foreach ($facts as [$label, $value])
  <tr>
    <td valign="top" style="padding:12px 12px 12px 0;border-bottom:1px solid {{ $c['hairline'] }};font-family:{{ $font }};font-size:14px;line-height:20px;color:{{ $c['muted'] }};width:40%;">{{ $label }}</td>
    <td valign="top" style="padding:12px 0;border-bottom:1px solid {{ $c['hairline'] }};font-family:{{ $font }};font-size:14px;line-height:20px;font-weight:600;color:{{ $c['text'] }};text-align:right;">{{ $value }}</td>
  </tr>
  @endforeach
</table>
