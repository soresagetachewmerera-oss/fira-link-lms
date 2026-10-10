$(document).ready(function() {
    let quizTimer = null;
    let quizStartTime = null;
    let quizTimeLimit = null;
    let quizTimeExpired = false;

    if ($('.course-access-fullscreen').length) {
        $('header, footer, .breadcrumb-area, .back-to-top, .mouseCursor').hide();
        $('body').css('overflow', 'hidden');
    }

    $('#sidebar-toggle').on('click', function() {
        $('#course-sidebar-wrapper').toggleClass('open');
    });

    $(document).on('click', function(e) {
        if ($(window).width() <= 991) {
            if (!$(e.target).closest('.course-sidebar-wrapper, #sidebar-toggle').length) {
                $('#course-sidebar-wrapper').removeClass('open');
            }
        }
    });

    $(document).on('click', '.chapter-header', function(e) {
        if ($(e.target).hasClass('chapter-progress')) {
            return;
        }
        
        const $chapterContent = $(this).next('.chapter-content');
        const $icon = $(this).find('.chapter-toggle-icon');
        const $chapterSection = $(this).closest('.chapter-section');
        
        $chapterContent.slideToggle(300);
        $chapterSection.toggleClass('collapsed');
        
        if ($chapterSection.hasClass('collapsed')) {
            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
        } else {
            $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
        }
    });

    $(document).on('click', '.quiz-option-label', function(e) {
        e.preventDefault();
        const radioInput = $(this).prev('input[type="radio"]');
        if (radioInput.length) {
            radioInput.prop('checked', true).trigger('change');
        }
    });

    $('.lesson-item, .quiz-item, .assignment-item').on('click', function() {
        const itemId = $(this).data('item-id');
        const itemType = $(this).data('item-type');
        const courseId = $(this).data('course-id');
        const isAccessible = $(this).data('accessible') == 1;

        if (!isAccessible) {
            if (window.toastMagic) {
                window.toastMagic.warning('Content Locked', 'Please complete the previous lessons/quizzes/assignments before accessing this content.');
            } else {
                alert('Please complete the previous lessons/quizzes/assignments before accessing this content.');
            }
            return;
        }

        $('.lesson-item, .quiz-item, .assignment-item').removeClass('active');
        $(this).addClass('active');

        loadItem(itemId, itemType, courseId);
    });

    function loadItem(itemId, itemType, courseId) {
        const url = itemType === 'lesson' 
            ? `/student/courses/${courseId}/lesson/load`
            : itemType === 'quiz'
            ? `/student/courses/${courseId}/quiz/${itemId}/load`
            : `/student/courses/${courseId}/assignment/${itemId}/load`;

        const data = {
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        if (itemType === 'lesson') {
            data.lesson_id = itemId;
            data.course_id = courseId;
        } else {
            data.course_id = courseId;
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                    currentItemId = itemId;
                    currentItemType = response.type || itemType;
                    
                    $('#current-item-title').text(response.title);
                    
                    let iconClass = 'fas fa-play-circle';
                    if (response.type === 'quiz') {
                        iconClass = 'fas fa-question-circle';
                    } else if (response.type === 'assignment') {
                        iconClass = 'fas fa-file-alt';
                    }
                    $('.course-header-play-icon').attr('class', iconClass + ' course-header-play-icon');
                    
                    let durationHtml = '';
                    if (response.duration && response.type === 'lesson') {
                        const duration = parseInt(response.duration);
                        const hours = Math.floor(duration / 60);
                        const minutes = duration % 60;
                        const durationFormatted = hours > 0 
                            ? hours + ':' + String(minutes).padStart(2, '0')
                            : '0:' + String(minutes).padStart(2, '0');
                        durationHtml = `(${durationFormatted} min)`;
                    }
                    $('.course-header-duration').text(durationHtml);
                    
                    if ($(window).width() <= 991) {
                        $('#course-sidebar-wrapper').removeClass('open');
                    }

                    if (itemType === 'lesson' && response.type === 'lesson') {
                        setTimeout(function() {
                            initializeVideoPlayer();
                        }, 100);
                    } else if (itemType === 'quiz' || response.type === 'quiz') {
                        if (!response.is_completed) {
                            setTimeout(function() {
                                initializeQuizTimer();
                            }, 100);
                        }
                    }
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to load content';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }
                
                if (window.toastMagic) {
                    if (xhr.responseJSON && xhr.responseJSON.locked) {
                        window.toastMagic.warning('Content Locked', errorMessage);
                    } else {
                        window.toastMagic.error('Error', errorMessage);
                    }
                } else {
                    if (xhr.responseJSON && xhr.responseJSON.locked) {
                        alert(errorMessage);
                    } else {
                        alert('Error: ' + errorMessage);
                    }
                }
            }
        });
    }

    function initializeVideoPlayer() {
        if (videoPlayer) {
            videoPlayer.dispose();
            videoPlayer = null;
        }

        const videoElement = document.getElementById('lesson-video-player');
        if (videoElement) {
            videoPlayer = videojs('lesson-video-player', {
                controls: true,
                responsive: true,
                fluid: true,
                playbackRates: [0.5, 1, 1.25, 1.5, 2]
            });

            videoPlayer.ready(function() {
                videoPlayer.on('timeupdate', function() {
                    if (videoPlayer.duration()) {
                        const percentage = Math.round((videoPlayer.currentTime() / videoPlayer.duration()) * 100);
                        updateVideoProgress(percentage);
                    }
                });

                videoPlayer.on('ended', function() {
                    const lessonId = $('#mark-complete-btn').data('lesson-id');
                    const courseId = $('#mark-complete-btn').data('course-id');
                    if (lessonId && courseId) {
                        markComplete(lessonId, courseId, true); 
                    }
                });
            });
        }
    }

    function updateVideoProgress(percentage) {
        const lessonId = $('#mark-complete-btn').data('lesson-id');
        const courseId = $('#mark-complete-btn').data('course-id');
        
        if (lessonId && courseId) {
            $.ajax({
                url: `/student/courses/${courseId}/lesson/progress`,
                method: 'POST',
                data: {
                    lesson_id: lessonId,
                    course_id: courseId,
                    percentage: percentage,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.is_completed) {
                        $('#mark-complete-btn').html('<i class="fas fa-check-circle"></i> Completed').prop('disabled', true);
                    }
                }
            });
        }
    }

    function markComplete(lessonId, courseId, autoNavigate = false) {
        $.ajax({
            url: `/student/courses/${courseId}/lesson/complete`,
            method: 'POST',
            data: {
                lesson_id: lessonId,
                course_id: courseId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#mark-complete-btn').html('<i class="fas fa-check-circle"></i> Completed').prop('disabled', true);
                    
                    updateSidebarLessonStatus(lessonId, true);
                    
                    refreshSidebarAccessibility();
                    
                    if (window.toastMagic) {
                        window.toastMagic.success('Lesson Completed', 'Great job! Lesson marked as complete.');
                    }
                    
                    if (response.course_completed) {
                        setTimeout(function() {
                            if (window.toastMagic) {
                                window.toastMagic.success('Course Completed!', 'Congratulations! You have completed the entire course!');
                            }
                            window.location.href = `/student/courses/${courseId}/completion`;
                        }, 2000);
                    } else if (autoNavigate && response.next_item) {
                        setTimeout(function() {
                            if (response.next_item.item_id && response.next_item.type) {
                                loadItem(response.next_item.item_id, response.next_item.type, courseId);
                                if (window.toastMagic) {
                                    window.toastMagic.info('Moving Forward', 'Loading next item...');
                                }
                            }
                        }, 1500);
                    } else if (!autoNavigate) {
                        
                        setTimeout(function() {
                            refreshSidebarAccessibility();
                        }, 500);
                    }
                }
            }
        });
    }

    $(document).on('click', '#mark-complete-btn', function() {
        const lessonId = $(this).data('lesson-id');
        const courseId = $(this).data('course-id');
        markComplete(lessonId, courseId);
    });

    function initializeQuizTimer() {
        const timerElement = $('.floating-quiz-timer');
        if (timerElement.length) {
            quizTimeExpired = false;
            
            timerElement.removeClass('warning danger');
            
            const timeLimitMinutes = parseInt(timerElement.data('time-limit')) || 0;
            
            if (timeLimitMinutes > 0) {
                quizTimeLimit = timeLimitMinutes * 60; 
                quizStartTime = Date.now();

                if (quizTimer) {
                    clearInterval(quizTimer);
                }

                updateTimerDisplay();

                quizTimer = setInterval(function() {
                    updateTimerDisplay();
                }, 1000);
            }
        }
    }

    function updateTimerDisplay() {
        if (!quizStartTime || !quizTimeLimit) return;

        const elapsed = Math.floor((Date.now() - quizStartTime) / 1000);
        const remaining = quizTimeLimit - elapsed;

        if (remaining <= 0) {
            clearInterval(quizTimer);
            $('.floating-timer-text').text('00:00');
            $('#time-taken').val(Math.floor((Date.now() - quizStartTime) / 60));
            
            $('#quiz-form button[type="submit"]').prop('disabled', true).html('<i class="fas fa-clock"></i> Time Expired');
            
            quizTimeExpired = true;
            submitQuiz(true);
            return;
        }

        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        const timeFormatted = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        $('.floating-timer-text').text(timeFormatted);
        
        const $timer = $('.floating-quiz-timer');
        $timer.removeClass('warning danger');
        
        if (remaining <= 60) {
            $timer.addClass('danger');
        } else if (remaining <= 300) {
            $timer.addClass('warning');
        }
    }

    function submitQuiz(allowExpired = false) {
        const $form = $('#quiz-form');
        if (!$form.length) return;

        if (!allowExpired && quizTimeExpired) {
            if (window.toastMagic) {
                window.toastMagic.warning('Time Expired', 'Quiz time has ended. Please refresh the page to submit again.');
            } else {
                alert('Quiz time has ended. Please refresh the page to submit again.');
            }
            return;
        }

        if (quizTimer && quizStartTime) {
            const elapsed = Math.floor((Date.now() - quizStartTime) / 1000);
            $('#time-taken').val(Math.floor(elapsed / 60));
            clearInterval(quizTimer);
            quizTimer = null;
        }

        const quizId = $form.data('quiz-id');
        const courseId = $form.data('course-id');

        
        $form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        const answers = [];
        $('.quiz-radio-input:checked').each(function() {
            const name = $(this).attr('name');
            const match = name.match(/answers\[(\d+)\]/);
            if (match) {
                const questionId = parseInt(match[1]);
                const selectedAnswer = parseInt($(this).val());
                answers.push({
                    question_id: questionId,
                    selected_answer: selectedAnswer
                });
            }
        });

        $.ajax({
            url: `/student/courses/${courseId}/quiz/${quizId}/submit`,
            method: 'POST',
            data: {
                quiz_id: quizId,
                answers: answers,
                time_taken: $('#time-taken').val() || 0,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    quizTimeExpired = true;
                    $('#main-content').html(response.html);
                    
                    if (response.passed && response.quiz_id) {
                        updateSidebarQuizStatus(response.quiz_id, true);
                        refreshSidebarAccessibility();
                        
                        if (window.toastMagic) {
                            window.toastMagic.success('Quiz Passed!', 'Congratulations! You passed the quiz.');
                        }
                        
                        if (response.course_completed) {
                            setTimeout(function() {
                                if (window.toastMagic) {
                                    window.toastMagic.success('Course Completed!', 'Congratulations! You have completed the entire course!');
                                }
                                window.location.href = `/student/courses/${courseId}/completion`;
                            }, 3000);
                        } else if (response.next_item && response.next_item.item_id) {
                            setTimeout(function() {
                                loadItem(response.next_item.item_id, response.next_item.type, courseId);
                                if (window.toastMagic) {
                                    window.toastMagic.info('Moving Forward', 'Loading next item...');
                                }
                            }, 3000);
                        }
                    } else if (response.passed === false) {
                       
                        if (window.toastMagic) {
                            window.toastMagic.warning('Quiz Failed', 'You did not pass the quiz. You can retake it.');
                        }
                    }
                }
            },
            error: function(xhr) {
                
                if (quizTimeExpired) {
                    if (window.toastMagic) {
                        window.toastMagic.error('Submission Failed', 'Quiz time has expired. Please refresh the page to submit again.');
                    } else {
                        alert('Quiz time has expired. Please refresh the page to submit again.');
                    }
                    return;
                }
                
                
                $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Quiz');
                
                let errorMessage = 'Failed to submit quiz';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                if (window.toastMagic) {
                    window.toastMagic.error('Submission Failed', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    }

    $(document).on('submit', '#quiz-form', function(e) {
        e.preventDefault();
        
        if (quizTimeExpired) {
            if (window.toastMagic) {
                window.toastMagic.warning('Time Expired', 'Quiz time has ended. Please refresh the page to submit again.');
            } else {
                alert('Quiz time has ended. Please refresh the page to submit again.');
            }
            return false;
        }
        
        submitQuiz();
        return false;
    });

    $(document).on('submit', '#assignment-form', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const assignmentId = $(this).data('assignment-id');
        const courseId = $(this).data('course-id');

        formData.append('assignment_id', assignmentId);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            url: `/student/courses/${courseId}/assignment/${assignmentId}/submit`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                    
                    if (window.toastMagic) {
                        window.toastMagic.success('Assignment Submitted', 'Your assignment has been submitted successfully!');
                    }
                    
                    updateSidebarAssignmentStatus(assignmentId, true);
                    
                    refreshSidebarAccessibility();
                    
                    if (response.course_completed) {
                        setTimeout(function() {
                            if (window.toastMagic) {
                                window.toastMagic.success('Course Completed!', 'Congratulations! You have completed the entire course!');
                            }
                            window.location.href = `/student/courses/${courseId}/completion`;
                        }, 2500);
                    } else if (response.next_item && response.next_item.item_id) {
                        setTimeout(function() {
                            loadItem(response.next_item.item_id, response.next_item.type, courseId);
                            if (window.toastMagic) {
                                window.toastMagic.info('Moving Forward', 'Loading next item...');
                            }
                        }, 2000);
                    } else {
                        setTimeout(function() {
                            navigateItem('next');
                        }, 2000);
                    }
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to submit assignment';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                if (window.toastMagic) {
                    window.toastMagic.error('Submission Failed', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    });

    $(document).on('change', '#files', function() {
        const files = this.files;
        const preview = $('#file-preview');
        preview.empty();

        Array.from(files).forEach(function(file) {
            const fileItem = $('<div class="mb-2"></div>');
            fileItem.html(`<i class="fas fa-file"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`);
            preview.append(fileItem);
        });
    });

    $(document).on('click', '.retake-quiz-btn', function(e) {
        e.preventDefault();
        const quizId = $(this).data('quiz-id');
        const courseIdFromBtn = $(this).data('course-id');
        const $btn = $(this);
        
        const targetCourseId = courseIdFromBtn || courseId;
        
        if (!targetCourseId) {
            if (window.toastMagic) {
                window.toastMagic.error('Error', 'Course ID not found. Please refresh the page.');
            } else {
                alert('Error: Course ID not found. Please refresh the page.');
            }
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            url: `/student/courses/${targetCourseId}/quiz/${quizId}/load`,
            method: 'POST',
            data: {
                retake: true,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                    currentItemId = quizId;
                    currentItemType = 'quiz';
                    
                    $('#current-item-title').text(response.title);
                    $('.course-header-play-icon').attr('class', 'fas fa-question-circle course-header-play-icon');
                    $('.course-header-duration').text('');
                    
                    setTimeout(function() {
                        initializeQuizTimer();
                    }, 100);
                } else {
                    $btn.prop('disabled', false).html('<i class="fas fa-redo"></i> Retake Quiz');
                    if (window.toastMagic) {
                        window.toastMagic.error('Error', response.message || 'Failed to load quiz.');
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-redo"></i> Retake Quiz');
                let errorMessage = 'Failed to load quiz';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (window.toastMagic) {
                    window.toastMagic.error('Error', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    });

    $(document).on('click', '.view-results-btn', function(e) {
        e.preventDefault();
        const attemptId = $(this).data('attempt-id');
        const $btn = $(this);
        
        if (!courseId) {
            if (window.toastMagic) {
                window.toastMagic.error('Error', 'Course ID not found. Please refresh the page.');
            } else {
                alert('Error: Course ID not found. Please refresh the page.');
            }
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            url: `/student/courses/${courseId}/quiz/attempt/${attemptId}/results`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                } else {
                    $btn.prop('disabled', false).html('View Results <i class="fas fa-eye"></i>');
                    if (window.toastMagic) {
                        window.toastMagic.error('Error', response.message || 'Failed to load quiz results.');
                    } else {
                        alert('Error: ' + (response.message || 'Failed to load quiz results.'));
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('View Results <i class="fas fa-eye"></i>');
                let errorMessage = 'Failed to load quiz results';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (window.toastMagic) {
                    window.toastMagic.error('Error', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    });

    $('#next-btn').on('click', function() {
        navigateItem('next');
    });

    $('#prev-btn').on('click', function() {
        navigateItem('previous');
    });

    function navigateItem(direction) {
        const url = direction === 'next' 
            ? `/student/courses/${courseId}/item/${currentItemId}/next`
            : `/student/courses/${courseId}/item/${currentItemId}/previous`;

        $.ajax({
            url: url,
            method: 'GET',
            data: {
                type: currentItemType
            },
            success: function(response) {
                if (response.success && response.item_id) {
                    loadItem(response.item_id, response.type, courseId);
                }
            }
        });
    }

    function updateSidebarLessonStatus(lessonId, isCompleted) {
        const $lessonItem = $(`.curriculum-item.lesson-item[data-item-id="${lessonId}"]`);
        if ($lessonItem.length) {
            const $statusIcon = $lessonItem.find('.curriculum-item-status');
            
            if (isCompleted) {
                $lessonItem.removeClass('locked');
                $statusIcon.html('<i class="fas fa-check-circle status-completed"></i>');
                
                const $topicSection = $lessonItem.closest('.chapter-section');
                if ($topicSection.length) {
                    const $progressBadge = $topicSection.find('.chapter-progress');
                    if ($progressBadge.length) {
                        const progressText = $progressBadge.text().split('/');
                        if (progressText.length === 2) {
                            const completed = parseInt(progressText[0]) + 1;
                            const total = parseInt(progressText[1]);
                            $progressBadge.text(`${completed}/${total}`);
                        }
                    }
                }
            }
        }
    }

    function updateSidebarQuizStatus(quizId, isCompleted) {
        const $quizItem = $(`.curriculum-item.quiz-item[data-item-id="${quizId}"]`);
        if ($quizItem.length) {
            const $statusIcon = $quizItem.find('.curriculum-item-status');
            
            if (isCompleted) {
                $quizItem.removeClass('locked');
                $statusIcon.html('<i class="fas fa-check-circle status-completed"></i>');
                
                const $topicSection = $quizItem.closest('.chapter-section');
                if ($topicSection.length) {
                    const $progressBadge = $topicSection.find('.chapter-progress');
                    if ($progressBadge.length) {
                        const progressText = $progressBadge.text().split('/');
                        if (progressText.length === 2) {
                            const completed = parseInt(progressText[0]) + 1;
                            const total = parseInt(progressText[1]);
                            $progressBadge.text(`${completed}/${total}`);
                        }
                    }
                }
            }
        }
    }

    function updateSidebarAssignmentStatus(assignmentId, isCompleted) {
        const $assignmentItem = $(`.curriculum-item.assignment-item[data-item-id="${assignmentId}"]`);
        if ($assignmentItem.length) {
            const $statusIcon = $assignmentItem.find('.curriculum-item-status');
            
            if (isCompleted) {
                $assignmentItem.removeClass('locked');
                $statusIcon.html('<i class="fas fa-check-circle status-completed"></i>');
                
                const $topicSection = $assignmentItem.closest('.chapter-section');
                if ($topicSection.length) {
                    const $progressBadge = $topicSection.find('.chapter-progress');
                    if ($progressBadge.length) {
                        const progressText = $progressBadge.text().split('/');
                        if (progressText.length === 2) {
                            const completed = parseInt(progressText[0]) + 1;
                            const total = parseInt(progressText[1]);
                            $progressBadge.text(`${completed}/${total}`);
                        }
                    }
                }
            }
        }
    }

    function refreshSidebarAccessibility() {
        $.ajax({
            url: `/student/courses/${courseId}/sidebar/refresh`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#course-sidebar').html(response.html);
                    
                    if (response.accessibleItems) {
                        Object.keys(response.accessibleItems).forEach(function(key) {
                            const isAccessible = response.accessibleItems[key];
                            const parts = key.split('_');
                            const type = parts[0];
                            const itemId = parts.slice(1).join('_');
                            const $item = $(`.curriculum-item.${type}-item[data-item-id="${itemId}"]`);
                            
                            if ($item.length) {
                                if (isAccessible) {
                                    $item.removeClass('locked');
                                    $item.attr('data-accessible', '1');
                                    const $icon = $item.find('.curriculum-item-icon');
                                    if ($icon.length) {
                                        if (type === 'lesson') {
                                            $icon.html('<i class="fas fa-play"></i>');
                                        } else if (type === 'quiz') {
                                            $icon.html('<i class="fas fa-comments"></i>');
                                        } else {
                                            $icon.html('<i class="fas fa-file-alt"></i>');
                                        }
                                    }
                                    const $status = $item.find('.curriculum-item-status');
                                    if ($status.find('.fa-lock').length) {
                                        $status.html('<div class="status-circle"></div>');
                                    }
                                } else {
                                    $item.addClass('locked');
                                    $item.attr('data-accessible', '0');
                                    const $icon = $item.find('.curriculum-item-icon');
                                    if ($icon.length) {
                                        $icon.html('<i class="fas fa-lock"></i>');
                                    }
                                }
                            }
                        });
                    }
                }
            },
            error: function(xhr) {
            }
        });
    }

    let searchTimeout;
    $('#search-input').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#search-results').hide().empty();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: `/student/courses/${courseId}/search`,
                method: 'POST',
                data: {
                    q: query,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.results.length > 0) {
                        const resultsHtml = response.results.map(function(result) {
                            return `<div class="search-result-item p-2 border-bottom" 
                                    data-item-id="${result.id}" 
                                    data-item-type="${result.type}" 
                                    style="cursor: pointer;">
                                <strong>${result.title}</strong> (${result.type}) - ${result.topic}
                            </div>`;
                        }).join('');
                        $('#search-results').html(resultsHtml).show();
                    } else {
                        $('#search-results').html('<div class="p-2">No results found</div>').show();
                    }
                }
            });
        }, 500);
    });

    $(document).on('click', '.search-result-item', function() {
        const itemId = $(this).data('item-id');
        const itemType = $(this).data('item-type');
        loadItem(itemId, itemType, courseId);
        $('#search-results').hide();
        $('#search-input').val('');
    });

    if (currentItemType === 'lesson') {
        setTimeout(function() {
            initializeVideoPlayer();
        }, 500);
    } else if (currentItemType === 'quiz') {
        if ($('#quiz-form').length) {
            setTimeout(function() {
                initializeQuizTimer();
            }, 500);
        }
    }
});

// Global Forum State Variables
let currentForumFilter = 'all';
let currentForumSearch = '';

// Load Discussions
function loadDiscussions(filter = 'all', search = '') {
    currentForumFilter = filter;
    currentForumSearch = search;
    
    $('#forum-container').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading discussions...</p>
        </div>
    `);

    $.ajax({
        url: `/student/courses/${courseId}/discussions`,
        method: 'GET',
        data: {
            filter: filter,
            search: search
        },
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
            } else {
                $('#forum-container').html(`<div class="alert alert-danger">${response.error || 'Failed to load discussions'}</div>`);
            }
        },
        error: function(xhr) {
            $('#forum-container').html(`<div class="alert alert-danger">Error fetching discussions. Please try again.</div>`);
        }
    });
}

// Filter and Search Triggers
function loadDiscussionsFiltered(filter) {
    loadDiscussions(filter, currentForumSearch);
}

function searchForum(event) {
    event.preventDefault();
    const searchVal = $('#forum-search-input').val();
    loadDiscussions(currentForumFilter, searchVal);
}

// Create new discussion thread
function submitNewDiscussion(event) {
    event.preventDefault();
    const form = $('#new-discussion-form');
    const submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Posting...');

    $.ajax({
        url: `/student/courses/${courseId}/discussions`,
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                // Reset form and collapse
                form[0].reset();
                $('#askQuestionForm').removeClass('show');
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Discussion Thread Posted!', response.message);
                } else {
                    alert(response.message);
                }
            } else {
                alert(response.error || 'Failed to post discussion');
            }
        },
        error: function(xhr) {
            const err = xhr.responseJSON ? xhr.responseJSON.error : 'An error occurred';
            alert(err);
        },
        complete: function() {
            submitBtn.prop('disabled', false).text('Post Thread');
        }
    });
}

// Show a discussion thread's details
function showDiscussion(discussionId) {
    $('#forum-container').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading discussion details...</p>
        </div>
    `);

    $.ajax({
        url: `/student/courses/${courseId}/discussions/${discussionId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
            }
        },
        error: function() {
            $('#forum-container').html('<div class="alert alert-danger">Failed to load discussion details.</div>');
        }
    });
}

// Submit reply
function submitReply(event, discussionId) {
    event.preventDefault();
    const form = $('#new-reply-form');
    const submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Posting...');

    $.ajax({
        url: `/student/courses/${courseId}/discussions/${discussionId}/replies`,
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Success', response.message);
                }
            }
        },
        error: function(xhr) {
            alert('Failed to post reply. Please try again.');
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-reply me-1"></i> Post Reply');
        }
    });
}

// Toggle likes
function toggleLike(discussionId, event) {
    event.stopPropagation();
    const btn = $(event.currentTarget);
    btn.prop('disabled', true);

    $.ajax({
        url: `/student/courses/${courseId}/discussions/${discussionId}/like`,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (response.liked) {
                    btn.addClass('liked');
                } else {
                    btn.removeClass('liked');
                }
                btn.find('.like-count').text(response.likes_count);
            }
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
}

function toggleReplyLike(replyId, event) {
    const btn = $(event.currentTarget);
    btn.prop('disabled', true);

    $.ajax({
        url: `/student/courses/${courseId}/discussions/replies/${replyId}/like`,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (response.liked) {
                    btn.addClass('liked');
                } else {
                    btn.removeClass('liked');
                }
                btn.find('.like-count').text(response.likes_count);
            }
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
}

// Delete discussion thread
function deleteDiscussion(discussionId, event) {
    event.stopPropagation();
    if (!confirm('Are you sure you want to delete this discussion thread? All replies will be permanently deleted.')) {
        return;
    }

    $.ajax({
        url: `/student/courses/${courseId}/discussions/${discussionId}`,
        method: 'POST',
        data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Success', response.message);
                }
            }
        },
        error: function(xhr) {
            alert('Failed to delete discussion.');
        }
    });
}

// Delete reply
function deleteReply(discussionId, replyId, event) {
    if (!confirm('Are you sure you want to delete this reply?')) {
        return;
    }

    $.ajax({
        url: `/student/courses/${courseId}/discussions/${discussionId}/replies/${replyId}`,
        method: 'POST',
        data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Success', response.message);
                }
            }
        },
        error: function() {
            alert('Failed to delete reply.');
        }
    });
}

// AJAX Pagination Click Handler
$(document).on('click', '.forum-pagination a', function(e) {
    e.preventDefault();
    let url = $(this).attr('href');
    
    $('#forum-container').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading discussions...</p>
        </div>
    `);

    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                // Scroll to top of forum container
                $('#forum-view-wrapper').animate({ scrollTop: 0 }, 'slow');
            }
        }
    });
});


