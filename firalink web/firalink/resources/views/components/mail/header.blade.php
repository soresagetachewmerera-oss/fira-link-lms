@php($c = config('mail_brand.colors'))
@php($logo = config('mail_brand.logo'))
<tr>
  <td class="fl-pad" style="padding:24px 32px 20px 32px;background-color:{{ $c['white'] }};border:1px solid {{ $c['hairline'] }};border-bottom:0;border-radius:{{ config('mail_brand.radius.card') }}px {{ config('mail_brand.radius.card') }}px 0 0;" align="left">
    <a href="{{ rtrim(config('app.url'), '/') }}/" style="text-decoration:none;">
      <img src="{{ asset($logo['path']) }}" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" alt="Fira Link Business Solutions" style="display:block;border:0;outline:none;width:{{ $logo['width'] }}px;height:{{ $logo['height'] }}px;font-family:{{ config('mail_brand.font') }};font-size:16px;font-weight:600;color:{{ $c['purple'] }};">
    </a>
  </td>
</tr>
<tr>
  <td style="padding:0 32px;background-color:{{ $c['white'] }};border-left:1px solid {{ $c['hairline'] }};border-right:1px solid {{ $c['hairline'] }};" class="fl-pad">
    <div style="height:1px;line-height:1px;font-size:1px;background-color:{{ $c['hairline'] }};">&nbsp;</div>
  </td>
</tr>
