@props(['locale' => 'en', 'title' => 'Fira Link', 'preheader' => ''])
@php($c = config('mail_brand.colors'))
@php($font = config('mail_brand.font'))
<!DOCTYPE html>
<html lang="{{ $locale }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
<title>{{ $title }}</title>
{{-- Only what cannot be inline: the phone layout. Everything else is inline on each element. --}}
<style>
  body { margin: 0; padding: 0; }
  a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
  @media only screen and (max-width: 620px) {
    .fl-container { width: 100% !important; }
    .fl-pad { padding-left: 20px !important; padding-right: 20px !important; }
    .fl-card-pad { padding: 20px !important; }
    .fl-h1 { font-size: 22px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:{{ $c['page'] }};font-family:{{ $font }};color:{{ $c['text'] }};-webkit-text-size-adjust:100%;">
{{-- Preheader: shown next to the subject in the inbox list, never the code. --}}
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:{{ $c['page'] }};opacity:0;">{{ $preheader }}&#8203;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;&nbsp;&#847;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $c['page'] }};">
  <tr>
    <td align="center" style="padding:24px 12px;">
      <table role="presentation" class="fl-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">
        {{ $slot }}
      </table>
    </td>
  </tr>
</table>
</body>
</html>
