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
            </div>
        </div>
    </div>
@endif
