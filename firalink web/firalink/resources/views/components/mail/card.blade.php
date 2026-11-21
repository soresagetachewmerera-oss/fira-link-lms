@php($c = config('mail_brand.colors'))
@php($r = config('mail_brand.radius.card'))
<tr>
  <td class="fl-card-pad" style="padding:32px;background-color:{{ $c['white'] }};border:1px solid {{ $c['hairline'] }};border-top:0;border-radius:0 0 {{ $r }}px {{ $r }}px;">
    {{ $slot }}
  </td>
</tr>
