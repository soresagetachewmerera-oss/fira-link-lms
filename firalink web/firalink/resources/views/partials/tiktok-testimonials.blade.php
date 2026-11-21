@php
    $testimonials = config('fira.learner_testimonials', []);
    if (empty($testimonials)) {
        // Fallback to legacy config key if present
        $testimonials = config('fira.tiktok_testimonials', []);
    }
    // Present the learner videos directly without thumbnails
    $carouselCards = $testimonials;
@endphp

@if(!empty($carouselCards))
<!-- What Learners Say About Our Platform - Direct Video Showcase Section -->
<section id="learner-stories-section" class="fira-learner-section" aria-labelledby="learner-stories-heading">
    <div class="container">
        
        <!-- 1. Centered Section Header -->
        <div class="fira-learner-header text-center">
            <p class="fira-route"><span class="fira-route__line" aria-hidden="true"><i></i><i></i><i></i></span>Learner stories</p>

            <h2 id="learner-stories-heading" class="fira-learner-heading">
                What learners say
            </h2>
            
            <p class="fira-learner-subheading">
                Hear directly from the entrepreneurs and professionals learning to trade with Fira Link.
            </p>

            <div class="fira-learner-sub-meta">
                <span class="fira-verified-pill">Learner video reviews</span>
                <span class="fira-meta-dot" aria-hidden="true">•</span>
                <a href="https://www.tiktok.com/@firaol_kebede_25" target="_blank" rel="noopener noreferrer" class="fira-channel-pill" title="Visit @firaol_kebede_25 on TikTok">
                    <svg class="fira-tiktok-svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.04-.1z"/>
                    </svg>
                    <span>@firaol_kebede_25</span>
                    <i class="fa-solid fa-arrow-up-right-from-square fira-ext-mini" aria-hidden="true"></i>
                </a>
            </div>
        </div>

    </div><!-- /.container -->

    <!-- 2. Video Carousel Showcase (Direct Videos Only - No Static Thumbnails) -->
    <div class="fira-carousel-outer-wrapper">
        
        <!-- Left & Right Fade Masks -->
        <div class="fira-edge-fade fira-edge-fade-left" aria-hidden="true"></div>
        <div class="fira-edge-fade fira-edge-fade-right" aria-hidden="true"></div>

        <!-- Left/Right Purple Circular Arrow Buttons -->
        <button type="button" class="fira-carousel-nav-btn fira-nav-prev" id="fira-carousel-prev-btn" aria-label="Previous videos" title="Previous videos">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" class="fira-carousel-nav-btn fira-nav-next" id="fira-carousel-next-btn" aria-label="Next videos" title="Next videos">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>

        <!-- Carousel Viewport & Video Track -->
        <div class="fira-carousel-viewport" id="fira-carousel-viewport" tabindex="0" role="region" aria-label="Learner video testimonials carousel">
            <div class="fira-carousel-track" id="fira-carousel-track">
                @foreach($carouselCards as $index => $item)
                    @php
                        $uniqueKey = ($item['id'] ?? 'vid') . '-' . $index;
                        $name = $item['name'] ?? 'Firaol Kebede';
                        $role = $item['role'] ?? 'Trade Entrepreneur';
                        $course = $item['course'] ?? 'Trade Operations';
                        $vid = $item['id'] ?? '';
                        $vUrl = $item['videoUrl'] ?? ('https://www.tiktok.com/@firaol_kebede_25/video/' . $vid);
                    @endphp
                    <div class="fira-carousel-item" data-index="{{ $index }}">
                        <div 
                            class="fira-video-card fira-video-card--direct" 
                            id="card-{{ $uniqueKey }}"
                            data-video-id="{{ $vid }}"
                            data-video-url="{{ $vUrl }}"
                            data-name="{{ $name }}"
                            data-role="{{ $role }}"
                            data-course="{{ $course }}"
                        >
                            <!-- Subtle Ambient Corner Glow -->
                            <div class="fira-card-ambient" aria-hidden="true"></div>

                            <!-- Direct Live Video Embed (No Static Thumbnail Image) -->
                            <div class="fira-card-media">
                                <iframe 
                                    src="https://www.tiktok.com/player/v1/{{ $vid }}?embed_from=oembed&music_info=1&description=1" 
                                    class="fira-tiktok-iframe"
                                    width="100%" 
                                    height="100%" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture; web-share" 
                                    loading="lazy"
                                    title="Learner review by {{ $name }} - {{ $course }}"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div><!-- /.fira-carousel-outer-wrapper -->

    <div class="container">
        <!-- Bottom Community Action Banner -->
        <div class="fira-learner-bottom-cta text-center">
            <div class="fira-learner-cta-box">
                <div class="fira-cta-text">
                    <i class="fa-solid fa-graduation-cap fira-purple-icon" aria-hidden="true"></i>
                    <span>Learn export operations, customs clearance and the Djibouti corridor at your own pace</span>
                </div>
                <div class="fira-cta-buttons">
                    <a href="{{ route('courses') }}" class="theme-btn fira-btn-purple">
                        Explore courses <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="https://www.tiktok.com/@firaol_kebede_25" target="_blank" rel="noopener noreferrer" class="fira-btn-outline-dark">
                        <svg class="fira-tiktok-svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.04-.1z"/>
                        </svg>
                        Follow @firaol_kebede_25
                    </a>
                </div>
            </div>
        </div>
    </div><!-- /.container -->

</section>

<!-- Scoped JavaScript for Smooth Video Carousel Navigation -->
<script>
(function() {
    'use strict';

    function initLearnerCarousel() {
        const viewport = document.getElementById('fira-carousel-viewport');
        const track = document.getElementById('fira-carousel-track');
        const prevBtn = document.getElementById('fira-carousel-prev-btn');
        const nextBtn = document.getElementById('fira-carousel-next-btn');

        function getCardStep() {
            const firstCard = track ? track.querySelector('.fira-carousel-item') : null;
            if (firstCard) {
                return firstCard.offsetWidth + 20; // card width + track gap
            }
            return 345;
        }

        if (prevBtn && viewport) {
            prevBtn.addEventListener('click', function() {
                const step = getCardStep();
                viewport.scrollBy({ left: -step, behavior: 'smooth' });
            });
        }

        if (nextBtn && viewport) {
            nextBtn.addEventListener('click', function() {
                const step = getCardStep();
                viewport.scrollBy({ left: step, behavior: 'smooth' });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLearnerCarousel);
    } else {
        initLearnerCarousel();
    }
})();
</script>
@endif
