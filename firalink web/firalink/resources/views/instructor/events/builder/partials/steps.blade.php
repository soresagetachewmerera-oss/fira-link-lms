<div class="builder-steps-wrapper wow fadeInUp" data-wow-delay=".05s">
    <ul class="builder-steps-list">
        @foreach ($steps as $index => $step)
            @php
                $classes = [];
                if ($step['active']) {
                    $classes[] = 'active';
                }
                if ($step['completed']) {
                    $classes[] = 'completed';
                }
                if ($step['locked']) {
                    $classes[] = 'locked';
                }
            @endphp
            <li class="builder-step {{ implode(' ', $classes) }}">
                @if ($step['url'])
                    <a href="{{ $step['url'] }}" class="builder-step-link">
                    @else
                        <span class="builder-step-link">
                @endif
                <span class="builder-step-index">{{ sprintf('%02d', $loop->iteration) }}</span>
                <span class="builder-step-label">{{ $step['label'] }}</span>
                @if ($step['completed'])
                    <span class="builder-step-status"><i class="fa-solid fa-check"></i></span>
                @endif
                @if ($step['url'])
                    </a>
                @else
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
