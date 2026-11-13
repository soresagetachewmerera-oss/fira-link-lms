@php
    $introVideo = config('fira.intro_video', []);
    $videoUrl = $introVideo['url'] ?? asset('videos/fira-link-prime.mp4');
    $videoTitle = $introVideo['title'] ?? 'Discover What You Can Do With Fira Link';
@endphp

<!-- Video Modal Start -->
<div 
    id="fira-video-modal" 
    class="fira-modal-backdrop" 
    role="dialog" 
    aria-modal="true" 
    aria-labelledby="fira-video-modal-title"
    aria-hidden="true"
    tabindex="-1"
>
    <div class="fira-modal-dialog">
        <!-- Modal Header Bar -->
        <div class="fira-modal-header">
            <div class="fira-modal-title-wrap">
                <i class="fa-solid fa-circle-play purple-icon me-2"></i>
                <h3 id="fira-video-modal-title" class="fira-modal-title">{{ $videoTitle }}</h3>
            </div>
            
            <button 
                type="button" 
                id="fira-modal-close-btn" 
                class="fira-modal-close" 
                aria-label="Close introduction video modal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Video Player Body (16:9 Frame) -->
        <div class="fira-modal-body">
            <div class="fira-modal-video-wrapper">
                <video 
                    id="fira-active-video-element"
                    class="fira-native-video"
                    controls
                    playsinline
                    preload="metadata"
                >
                    <source src="{{ asset($videoUrl) }}" type="video/mp4">
                    Your browser does not support HTML5 video playback. Please upgrade your browser.
                </video>
            </div>
        </div>

        <!-- Modal Footer / Quick Action -->
        <div class="fira-modal-footer">
            <div class="fira-modal-footer-caption">
                <span>Ready to start your trade journey?</span>
            </div>
            <div class="fira-modal-footer-actions">
                <a href="{{ route('courses') }}" class="fira-btn-primary fira-btn-sm">
                    <span>Explore Courses</span>
                    <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
                <a href="{{ route('free-tools.index') }}" class="fira-btn-secondary fira-btn-sm">
                    <i class="fa-solid fa-calculator me-1"></i>
                    <span>Free Tools</span>
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Video Modal End -->

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('fira-video-modal');
        if (!modal) return;

        const video = document.getElementById('fira-active-video-element');
        const closeBtn = document.getElementById('fira-modal-close-btn');
        const cardTrigger = document.getElementById('fira-video-trigger');
        const heroVideoBtn = document.getElementById('hero-watch-intro-btn');
        let previousActiveElement = null;

        function openModal() {
            previousActiveElement = document.activeElement;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('fira-modal-locked');

            if (video) {
                // Play video smoothly
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        console.log('Autoplay prevented by browser policy, user can click play control:', error);
                    });
                }
            }

            if (closeBtn) {
                setTimeout(function() {
                    closeBtn.focus();
                }, 100);
            }
        }

        function closeModal() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('fira-modal-locked');

            if (video) {
                video.pause();
            }

            if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                previousActiveElement.focus();
            }
        }

        // Trigger on card click
        if (cardTrigger) {
            cardTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });

            cardTrigger.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openModal();
                }
            });
        }

        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal();
            });
        }

        // Click outside on backdrop to close
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });

        // Hero CTA button behavior:
        // Smooth scroll to intro video section and gently pulse the card
        if (heroVideoBtn) {
            heroVideoBtn.addEventListener('click', function(e) {
                const targetSec = document.getElementById('intro-video-section');
                if (targetSec) {
                    e.preventDefault();
                    const headerOffset = 80;
                    const elementPosition = targetSec.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });

                    // Highlight card after scroll
                    if (cardTrigger) {
                        setTimeout(function() {
                            cardTrigger.classList.add('fira-card-highlighted');
                            setTimeout(function() {
                                cardTrigger.classList.remove('fira-card-highlighted');
                            }, 1800);
                        }, 500);
                    }
                }
            });
        }
    });
})();
</script>
