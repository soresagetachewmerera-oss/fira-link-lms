@php
    $introVideo = config('fira.intro_video', []);
    $videoUrl = $introVideo['url'] ?? asset('videos/fira-link-prime.mp4');
    $videoPoster = $introVideo['poster'] ?? asset('assets/front/img/video/fira_video_poster.jpg');
    $videoTitle = $introVideo['title'] ?? 'Fira Link Platform Overview';
@endphp

<!-- Introduction Video Section (Only Video, Centered & Fully Responsive) -->
<section id="intro-video-section" class="fira-video-section fira-only-video-section" aria-label="Fira Link Video Overview">
    <div class="container fira-video-container">
        <div class="fira-video-showcase-wrap">
            <!-- Subtle Ambient Purple Glow -->
            <div class="fira-video-ambient-glow" aria-hidden="true"></div>

            <!-- Fully Responsive Video Player Container (16:9 Aspect Ratio) -->
            <div class="fira-video-player-container">
                <video 
                    id="fira-intro-video" 
                    class="fira-responsive-video"
                    controls 
                    playsinline 
                    preload="metadata" 
                    poster="{{ asset($videoPoster) }}"
                    aria-label="Platform Video"
                >
                    <source src="{{ asset($videoUrl) }}" type="video/mp4">
                    Your browser does not support the video tag. Please update your browser to watch this video.
                </video>
            </div>
        </div>
    </div>
</section>

<!-- Scoped Interaction for Hero Video Button Smooth-Scroll & Autoplay -->
<script>
(function() {
    'use strict';
    function initIntroVideo() {
        const video = document.getElementById('fira-intro-video');
        const heroBtn = document.getElementById('hero-watch-intro-btn');
        const section = document.getElementById('intro-video-section');

        if (heroBtn && video && section) {
            heroBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const navOffset = 90;
                const rect = section.getBoundingClientRect();
                const targetY = rect.top + window.pageYOffset - navOffset;

                window.scrollTo({
                    top: targetY,
                    behavior: 'smooth'
                });

                // Play video after smooth scroll reaches the section
                setTimeout(function() {
                    video.focus();
                    const playPromise = video.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(function() {
                            // Autoplay policy fallback: video remains ready for user interaction
                        });
                    }
                }, 600);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIntroVideo);
    } else {
        initIntroVideo();
    }
})();
</script>
