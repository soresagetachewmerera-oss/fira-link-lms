@props(['footer'])
@php($c = config('mail_brand.colors'))
@php($font = config('mail_brand.font'))
@php($link = "color:{$c['muted']};text-decoration:underline;")
<tr>
  <td class="fl-pad" style="padding:24px 32px 8px 32px;font-family:{{ $font }};font-size:13px;line-height:20px;color:{{ $c['muted'] }};">
    <p style="margin:0 0 8px 0;">{{ $footer['why'] }}</p>
    <p style="margin:0 0 8px 0;">{{ $footer['never_ask'] }}</p>
    <p style="margin:0 0 8px 0;">
      <a href="{{ $footer['support_url'] }}" style="{{ $link }}">{{ $footer['support_label'] }}</a>
      &nbsp;·&nbsp;
      <a href="{{ $footer['language_url'] }}" lang="{{ $footer['language_code'] }}" style="{{ $link }}">{{ $footer['language_label'] }}</a>
      @if (!empty($footer['preferences_url']))
      &nbsp;·&nbsp;
      <a href="{{ $footer['preferences_url'] }}" style="{{ $link }}">{{ $footer['preferences_label'] }}</a>
      @endif
    </p>
    <p style="margin:0;">{{ config('mail_brand.company') }} · {{ config('mail_brand.address') }}</p>
  </td>
</tr>
