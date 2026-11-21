{{-- The one email template (messaging slice 2). Content comes from EmailContent as blocks; no
     template builds its own header, footer or button. --}}
<x-mail.shell :locale="$content['locale']" :title="$content['subject']" :preheader="$content['preheader']">
  <x-mail.header />
  <x-mail.card>
    @foreach ($content['blocks'] as $block)
      @switch($block['type'])
        @case('eyebrow') <x-mail.eyebrow>{{ $block['text'] }}</x-mail.eyebrow> @break
        @case('heading') <x-mail.heading>{{ $block['text'] }}</x-mail.heading> @break
        @case('paragraph') <x-mail.paragraph>{{ $block['text'] }}</x-mail.paragraph> @break
        @case('help') <x-mail.paragraph :muted="true">{{ $block['text'] }}</x-mail.paragraph> @break
        @case('code') <x-mail.code-box :code="$block['code']" :label="$block['label'] ?? ''" /> @break
        @case('button') <x-mail.button :url="$block['url']" :fallback-label="$block['fallback'] ?? ''">{{ $block['text'] }}</x-mail.button> @break
        @case('facts') <x-mail.fact-list :facts="$block['facts']" /> @break
        @case('notice') <x-mail.notice>{{ $block['text'] }}</x-mail.notice> @break
        @case('status') <x-mail.status-pill :status="$block['status']">{{ $block['text'] }}</x-mail.status-pill> @break
        @case('divider') <x-mail.divider /> @break
      @endswitch
    @endforeach
  </x-mail.card>
  <x-mail.footer :footer="$content['footer']" />
</x-mail.shell>
