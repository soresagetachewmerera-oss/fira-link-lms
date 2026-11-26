<div class="fira-hero__beans" aria-hidden="true">
    @foreach([1, 2, 3, 4, 5] as $idx)
        <svg class="fira-hero-bean fira-hero-bean--{{ $idx }}" viewBox="0 0 80 104" focusable="false">
            <defs>
                <radialGradient id="fira-bean-grad-{{ $idx }}" cx="35%" cy="30%" r="72%">
                    <stop offset="0%" stop-color="var(--fira-hero-bean-light)"></stop>
                    <stop offset="56%" stop-color="var(--fira-primary)"></stop>
                    <stop offset="100%" stop-color="var(--fira-hero-bean-dark)"></stop>
                </radialGradient>
            </defs>
            <ellipse cx="40" cy="52" rx="30" ry="44" fill="url(#fira-bean-grad-{{ $idx }})"></ellipse>
            <ellipse class="fira-hero-bean__highlight" cx="28" cy="30" rx="9" ry="15" transform="rotate(-18 28 30)"></ellipse>
            <path class="fira-hero-bean__crease" d="M40 12 C 48 34, 32 68, 40 92" fill="none" stroke-width="3.2" stroke-linecap="round"></path>
        </svg>
    @endforeach
</div>
