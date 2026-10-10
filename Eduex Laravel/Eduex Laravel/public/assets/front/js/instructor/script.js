/*
--------------------------------------------------------------
Instructor Pages JavaScript
--------------------------------------------------------------
*/

(function ($) {
    'use strict';

    function initFileUpload() {
        $('.instructor-file-upload input[type="file"]').on('change', function() {
            const file = this.files[0];
            const preview = $(this).closest('.instructor-form-clt').find('.instructor-file-preview');
            const text = $(this).closest('.instructor-file-upload').find('.instructor-file-upload-text');
            
            if (file) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html('<img src="' + e.target.result + '" alt="Preview">');
                        preview.show();
                    };
                    reader.readAsDataURL(file);
                }
                
                text.text(file.name);
            } else {
                preview.hide();
                text.text('Click or drag to upload file');
            }
        });
    }

    function initFormValidation() {
        $('.instructor-form').on('submit', function(e) {
            let isValid = true;
            
            $(this).find('input[required], textarea[required], select[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        $('.instructor-form input, .instructor-form textarea, .instructor-form select').on('input', function() {
            $(this).removeClass('is-invalid');
        });
    }

    function initProgressIndicator() {
        $('.instructor-progress-item').each(function(index) {
            if (index === 0) {
                $(this).addClass('active');
            }
        });
    }

    function initStepNavigation() {
        $('.instructor-step-next').on('click', function(e) {
            e.preventDefault();
            const currentStep = $('.instructor-progress-item.active');
            const nextStep = currentStep.next('.instructor-progress-item');
            
            if (nextStep.length) {
                currentStep.removeClass('active').addClass('completed');
                nextStep.addClass('active');
                
                $('html, body').animate({
                    scrollTop: 0
                }, 300);
            }
        });
        
        $('.instructor-step-prev').on('click', function(e) {
            e.preventDefault();
            const currentStep = $('.instructor-progress-item.active');
            const prevStep = currentStep.prev('.instructor-progress-item');
            
            if (prevStep.length) {
                currentStep.removeClass('active');
                prevStep.removeClass('completed').addClass('active');
                
                $('html, body').animate({
                    scrollTop: 0
                }, 300);
            }
        });
    }

    function initCharacterCounter() {
        $('.instructor-char-counter').each(function() {
            const $textarea = $(this).prev('textarea');
            const maxLength = parseInt($(this).data('max-length')) || 1000;
            
            const updateCounter = function() {
                const length = $textarea.val().length;
                $(this).text(length + ' / ' + maxLength);
            }.bind(this);
            
            $textarea.on('input', updateCounter);
            updateCounter();
        });
    }

    function initMobileMenuToggle() {
        const $toggle = $('.dashboard-menu-toggle');
        const $navWrapper = $('.dashboard-nav-wrapper');
        
        if ($toggle.length === 0) return;
        
        $toggle.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            $toggle.toggleClass('active');
            $navWrapper.toggleClass('menu-open');
        });
        
        $('.dashboard-nav a').on('click', function() {
            if ($(window).width() <= 768 && $navWrapper.hasClass('menu-open')) {
                $toggle.removeClass('active');
                $navWrapper.removeClass('menu-open');
            }
        });
        
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.dashboard-sidebar').length && 
                    $navWrapper.hasClass('menu-open')) {
                    $toggle.removeClass('active');
                    $navWrapper.removeClass('menu-open');
                }
            }
        });
        
        $('.dashboard-sidebar').on('click', function(e) {
            e.stopPropagation();
        });
    }

    function initStudentFilter() {
        $('.filter-btn').on('click', function() {
            const filter = $(this).data('filter');
            
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            if (filter === 'all') {
                $('.students-table tbody tr').fadeIn(300);
            } else {
                $('.students-table tbody tr').each(function() {
                    if ($(this).data('status') === filter) {
                        $(this).fadeIn(300);
                    } else {
                        $(this).fadeOut(300);
                    }
                });
            }
        });
    }

    function initStudentSearch() {
        $('#studentSearch').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.students-table tbody tr').each(function() {
                const studentName = $(this).find('.student-name').text().toLowerCase();
                const studentEmail = $(this).find('.student-email-small').text().toLowerCase();
                
                if (studentName.includes(searchTerm) || studentEmail.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    function initReviewFilter() {
        $('.reviews-filters .filter-btn').on('click', function() {
            const filter = $(this).data('filter');
            
            $('.reviews-filters .filter-btn').removeClass('active');
            $(this).addClass('active');
            
            if (filter === 'all') {
                $('.review-card').fadeIn(300);
            } else {
                $('.review-card').each(function() {
                    if ($(this).data('rating') == filter) {
                        $(this).fadeIn(300);
                    } else {
                        $(this).fadeOut(300);
                    }
                });
            }
        });
    }

    function initReplyForm() {
        $('.reply-btn').on('click', function() {
            const reviewId = $(this).data('review-id');
            const formContainer = $('#reply-form-' + reviewId);
            
            $('.reply-form-container').not(formContainer).slideUp(300);
            
            formContainer.slideToggle(300, function() {
                if (formContainer.is(':visible')) {
                    formContainer.find('.reply-textarea').focus();
                }
            });
        });

        $('.cancel-reply-btn').on('click', function(e) {
            e.preventDefault();
            const reviewId = $(this).data('review-id');
            const formContainer = $('#reply-form-' + reviewId);
            const form = formContainer.find('.reply-form');
            
            form[0].reset();
            
            formContainer.slideUp(300);
        });

    }



    $(document).ready(function() {
        initFileUpload();
        initFormValidation();
        initProgressIndicator();
        initStepNavigation();
        initCharacterCounter();
        initMobileMenuToggle();
        initStudentFilter();
        initStudentSearch();
        initReviewFilter();
        initReplyForm();
    });

})(jQuery);

