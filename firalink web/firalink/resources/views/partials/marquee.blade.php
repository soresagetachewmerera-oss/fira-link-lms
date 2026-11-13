@if ($marqueeItems->isNotEmpty())
    <div class="marquee-section">
        <div class="mycustom-marque theme-green-bg-1">
            <div class="scrolling-wrap">
                @foreach ($marqueeChunks as $chunk)
                    <div class="comm">
                        @foreach ($chunk as $item)
                            <div class="cmn-textslide text-color-2">
                                <img src="{{ $item['image'] }}" alt="marquee icon">
                                {{ $item['text'] }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @if ($marqueeChunks->count() === 1)
                    <div class="comm" aria-hidden="true">
                        @foreach ($marqueeChunks->first() as $item)
                            <div class="cmn-textslide text-color-2">
                                <img src="{{ $item['image'] }}" alt="marquee icon">
                                {{ $item['text'] }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
