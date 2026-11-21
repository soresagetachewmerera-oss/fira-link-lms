@foreach ($content['blocks'] as $block)
@switch($block['type'])
@case('heading')
{!! $block['text'] !!}

@break
@case('paragraph')
@case('help')
@case('notice')
{!! $block['text'] !!}

@break
@case('code')
{!! $block['label'] ?? '' !!} {!! $block['code'] !!}

@break
@case('button')
{!! $block['text'] !!}: {!! $block['url'] !!}

@break
@case('facts')
@foreach ($block['facts'] as [$label, $value])
{!! $label !!}: {!! $value !!}
@endforeach

@break
@case('status')
{!! $block['text'] !!}

@break
@endswitch
@endforeach
--
{!! $content['footer']['why'] !!}
{!! $content['footer']['never_ask'] !!}
{!! $content['footer']['support_label'] !!}: {!! $content['footer']['support_url'] !!}
{!! config('mail_brand.company') !!} · {!! config('mail_brand.address') !!}
