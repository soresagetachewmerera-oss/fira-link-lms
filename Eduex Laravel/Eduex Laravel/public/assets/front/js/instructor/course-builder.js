
let currentStep = 1;
let topics = [];
window.quillEditor = null;
let topicSortableInstance = null;
const contentSortableInstances = {};

$(document).ready(function () {
    // Initialize Quill Rich Text Editor first
    if ($('.rich-editor').length) {
        window.quillEditor = new Quill('.rich-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    ['link'],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['image']
                ]
            },
            placeholder: 'Describe your course...'
        });

        if (typeof courseData !== 'undefined' && courseData && courseData.description) {
            quillEditor.root.innerHTML = courseData.description;
        }

        $('.ql-toolbar').hide();

        $('.toolbar-btn').on('click', function (e) {
            e.preventDefault();
            const command = $(this).data('command');
            const value = $(this).data('value');

            if (command) {
                quillEditor.focus();
                if (value) {
                    quillEditor.format(command, value);
                } else {
                    quillEditor.format(command, !quillEditor.getFormat()[command]);
                }
            }
        });

        $('.format-select').on('change', function () {
            const format = $(this).val();
            quillEditor.format('header', format === 'Paragraph' ? false : parseInt(format.replace('Heading ', '')));
        });
    }

    const categorySelectEl = document.getElementById('course_category_id');
    const categoryLabelEl = document.getElementById('category_label');
    const languageSelectEl = document.getElementById('course_language_id');
    const languageLabelEl = document.getElementById('language_label');

    const syncTaxonomyLabels = () => {
        if (categorySelectEl && categoryLabelEl) {
            const selected = categorySelectEl.selectedOptions[0];
            if (selected && selected.value) {
                categoryLabelEl.value = selected.text.trim();
            } else if (selected && !selected.value) {
                categoryLabelEl.value = '';
            }
        }

        if (languageSelectEl && languageLabelEl) {
            const selected = languageSelectEl.selectedOptions[0];
            if (selected && selected.value) {
                languageLabelEl.value = selected.text.trim();
            } else if (selected && !selected.value) {
                languageLabelEl.value = '';
            }
        }
    };

    if (categorySelectEl && categoryLabelEl) {
        categorySelectEl.addEventListener('change', syncTaxonomyLabels);
    }

    if (languageSelectEl && languageLabelEl) {
        languageSelectEl.addEventListener('change', syncTaxonomyLabels);
    }

    syncTaxonomyLabels();

    // Initialize course data if editing
    if (typeof courseData !== 'undefined' && courseData) {

        if (categorySelectEl && courseData.course_category_id) {
            categorySelectEl.value = courseData.course_category_id;
        }

        if (languageSelectEl && courseData.course_language_id) {
            languageSelectEl.value = courseData.course_language_id;
        }

        topics = courseData.topics.map(function (topic) {
            return {
                id: topic.id || Date.now() + Math.random(),
                title: topic.title || '',
                description: topic.description || '',
                lessons: (topic.lessons || []).map(function (lesson) {
                    return {
                        id: lesson.id || Date.now() + Math.random(),
                        title: lesson.title || '',
                        type: 'lesson',
                        description: lesson.description || '',
                        videoType: lesson.videoType || 'url',
                        videoUrl: lesson.videoUrl || '',
                        videoFile: null,
                        videoPath: lesson.videoPath || null,
                        duration: lesson.duration || '',
                        isPreview: lesson.isPreview || false
                    };
                }),
                quizzes: (topic.quizzes || []).map(function (quiz) {
                    return {
                        id: quiz.id || Date.now() + Math.random(),
                        title: quiz.title || '',
                        type: 'quiz',
                        description: quiz.description || '',
                        questions: (quiz.questions || []).map(function (q) {
                            return {
                                id: q.id || Date.now() + Math.random(),
                                question: q.question || '',
                                type: q.type || 'multiple-choice',
                                options: q.options || [],
                                correctAnswer: q.correctAnswer || 0
                            };
                        }),
                        passingScore: quiz.passingScore || 60,
                        timeLimit: quiz.timeLimit || ''
                    };
                }),
                assignments: (topic.assignments || []).map(function (assignment) {
                    return {
                        id: assignment.id || Date.now() + Math.random(),
                        title: assignment.title || '',
                        type: 'assignment',
                        description: assignment.description || '',
                        instructions: assignment.instructions || '',
                        attachments: assignment.attachments || []
                    };
                })
            };
        });

        if (courseData.featured_image) {
            $('#featuredImagePreview').html(`<img src="${courseData.featured_image}" alt="Featured Image">`);
        }

        if (courseData.intro_video) {
            $('#introVideoPreview').html(`
                <i class="fa-solid fa-play-circle"></i>
                <span>Current video</span>
                <small>Click to change</small>
            `);
        }

        if (topics.length > 0) {
            topics.forEach(initialiseTopicItemsFromChildren);
            renderTopics();
        }
    }

    function toggleMaxStudentField() {
        const isChecked = $('#public_course').prop('checked');
        const maxStudentsField = $('#max_students');

        if (isChecked) {
            maxStudentsField.prop('disabled', true);
            maxStudentsField.css('opacity', '0.6');
            maxStudentsField.css('cursor', 'not-allowed');
        } else {
            maxStudentsField.prop('disabled', false);
            maxStudentsField.css('opacity', '1');
            maxStudentsField.css('cursor', 'text');
        }
    }

    toggleMaxStudentField();

    $('#public_course').on('change', toggleMaxStudentField);

    $('#schedule_enabled').on('change', function () {
        const isChecked = $(this).prop('checked');
        const datePicker = $('#schedule-date-picker');

        if (isChecked) {
            datePicker.slideDown(300);
        } else {
            datePicker.slideUp(300);
            $('#schedule_date').val('');
        }
    });

    $('.progress-step').on('click', function () {
        const step = $(this).data('step');
        changeStep(step);
    });

    $('.tab-btn').on('click', function () {
        const tab = $(this).data('tab');
        if (tab) {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-pane').removeClass('active');
            $(`#${tab}-tab`).addClass('active');
        }
    });

    $('input[name="pricing_model"]').on('change', function () {
        if ($(this).val() === 'paid') {
            $('#pricingFields').slideDown();
        } else {
            $('#pricingFields').slideUp();
        }
    });

    $('#featuredImage').on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#featuredImagePreview').html(`<img src="${e.target.result}" alt="Featured Image">`);
            };
            reader.readAsDataURL(file);
        }
    });
    $('#featuredImagePreview').on('click', function () {
        $('#featuredImage').click();
    });

    $('#introVideo').on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            $('#introVideoPreview').html(`
                <i class="fa-solid fa-play-circle"></i>
                <span>${file.name}</span>
                <small>00:15 min</small>
            `);
        }
    });
    $('#introVideoPreview').on('click', function () {
        $('#introVideo').click();
    });

    $('#courseBuilderForm').on('submit', function (e) {
        e.preventDefault();
        submitForm();
    });


});

function changeStep(step) {
    currentStep = step;
    $('.builder-step').removeClass('active');
    $(`.builder-step[data-step="${step}"]`).addClass('active');
    $('.progress-step').removeClass('active');
    $(`.progress-step[data-step="${step}"]`).addClass('active');

    if (step === 1) {
        $('.btn-prev').hide();
    } else {
        $('.btn-prev').show();
    }

    if (step === 3) {
        $('.btn-next').html('<i class="fa-solid fa-save"></i> Save as Draft');
        $('.btn-next').attr('onclick', 'submitForm(false)');
        $('#publishBtn').show();
    } else {
        $('.btn-next').html('Next <i class="fa-solid fa-chevron-right"></i>');
        $('.btn-next').attr('onclick', 'nextStep()');
        $('#publishBtn').hide();
    }
}

function nextStep() {
    if (currentStep < 3) {
        changeStep(currentStep + 1);
    }
}

function previousStep() {
    if (currentStep > 1) {
        changeStep(currentStep - 1);
    }
}

$(document).on('click', '#publishBtn', function () {
    submitForm(true);
});

function submitForm(publish = false) {
    const form = $('#courseBuilderForm')[0];
    const formData = new FormData();

    const csrfToken = $('input[name="_token"]').val();
    formData.append('_token', csrfToken);

    const methodInput = $('input[name="_method"]');
    if (methodInput.length > 0) {
        formData.append('_method', methodInput.val());
    }

    $(form).find('input[type="text"], input[type="number"], input[type="email"], input[type="url"], input[type="datetime-local"], select, textarea, input[type="radio"]:checked').each(function () {
        const name = $(this).attr('name');
        const value = $(this).val();
        if (name && value !== null && value !== undefined) {
            formData.append(name, value);
        }
    });

    const publicCourseCheckbox = $('#public_course');
    formData.append('public_course', publicCourseCheckbox.is(':checked') ? '1' : '0');

    const scheduleEnabledCheckbox = $('#schedule_enabled');
    formData.append('schedule_enabled', scheduleEnabledCheckbox.is(':checked') ? '1' : '0');

    const isLiveCourseCheckbox = $('#is_live_course');
    if (isLiveCourseCheckbox.length > 0) {
        formData.append('is_live_course', isLiveCourseCheckbox.is(':checked') ? '1' : '0');
    }

    const featuredImageInput = document.getElementById('featuredImage');
    if (featuredImageInput && featuredImageInput.files && featuredImageInput.files.length > 0) {
        formData.append('featured_image', featuredImageInput.files[0]);
    }

    const introVideoInput = document.getElementById('introVideo');
    if (introVideoInput && introVideoInput.files && introVideoInput.files.length > 0) {
        formData.append('intro_video', introVideoInput.files[0]);
    }

    if (publish) {
        formData.append('status', 'published');
        formData.append('publish', '1');
    } else {
        formData.append('status', 'draft');
    }

    if (quillEditor) {
        const content = quillEditor.root.innerHTML;
        $('.rich-editor-textarea').val(content);
        formData.append('description', content);
    }

    const topicsData = JSON.stringify(topics);
    formData.append('topics_json', topicsData);

    topics.forEach((topic, topicIndex) => {
        formData.append(`topics[${topicIndex}][title]`, topic.title || '');
        formData.append(`topics[${topicIndex}][description]`, topic.description || '');
        formData.append(`topics[${topicIndex}][order]`, topicIndex);

        if (topic.id) {
            formData.append(`topics[${topicIndex}][id]`, topic.id);
        }

        if (topic.lessons && topic.lessons.length > 0) {
            topic.lessons.forEach((lesson, lessonIndex) => {
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][title]`, lesson.title || '');
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][description]`, lesson.description || '');
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][video_type]`, lesson.videoType || 'url');
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][video_url]`, lesson.videoUrl || '');
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][duration]`, lesson.duration || '');
                const isPreview = lesson.isPreview === true || lesson.isPreview === 'true' || lesson.isPreview === 1 || lesson.isPreview === '1';
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][is_preview]`, isPreview ? '1' : '0');
                formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][order]`, lessonIndex);

                if (lesson.id) {
                    formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][id]`, lesson.id);
                }
                if (lesson.videoPath) {
                    formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][video_path]`, lesson.videoPath);
                }

                if (lesson.videoType === 'upload' && lesson.videoFile && lesson.videoFile.file) {
                    const videoFile = lesson.videoFile.file;
                    if (videoFile instanceof File) {
                        formData.append(`lesson_video_${topicIndex}_${lessonIndex}`, videoFile);
                        formData.append(`topics[${topicIndex}][lessons][${lessonIndex}][video_file]`, videoFile);
                    }
                }
            });
        }

        // Process quizzes
        if (topic.quizzes && topic.quizzes.length > 0) {
            topic.quizzes.forEach((quiz, quizIndex) => {
                formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][title]`, quiz.title || '');
                formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][description]`, quiz.description || '');
                formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][passing_score]`, quiz.passingScore || 60);
                formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][time_limit]`, quiz.timeLimit || '');
                formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][order]`, quizIndex);

                if (quiz.id) {
                    formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][id]`, quiz.id);
                }

                if (quiz.questions && quiz.questions.length > 0) {
                    quiz.questions.forEach((question, qIndex) => {
                        formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][questions][${qIndex}][question]`, question.question || '');
                        formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][questions][${qIndex}][type]`, question.type || 'multiple-choice');
                        formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][questions][${qIndex}][correct_answer]`, question.correctAnswer || 0);
                        formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][questions][${qIndex}][order]`, qIndex);

                        if (question.id) {
                            formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][questions][${qIndex}][id]`, question.id);
                        }

                        if (question.options && Array.isArray(question.options)) {
                            question.options.forEach((option, optIndex) => {
                                formData.append(`topics[${topicIndex}][quizzes][${quizIndex}][questions][${qIndex}][options][${optIndex}]`, option || '');
                            });
                        }
                    });
                }
            });
        }

        // Process assignments
        if (topic.assignments && topic.assignments.length > 0) {
            topic.assignments.forEach((assignment, assignmentIndex) => {
                formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][title]`, assignment.title || '');
                formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][description]`, assignment.description || '');
                formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][instructions]`, assignment.instructions || '');
                formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][order]`, assignmentIndex);

                if (assignment.id) {
                    formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][id]`, assignment.id);
                }

                if (assignment.attachments && assignment.attachments.length > 0) {
                    assignment.attachments.forEach((fileObj, fileIndex) => {
                        if (fileObj.id) {
                            formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][existing_files][${fileIndex}][id]`, fileObj.id);
                        }
                        if (fileObj.url) {
                            formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][existing_files][${fileIndex}][url]`, fileObj.url);
                        }
                        if (fileObj.name) {
                            formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][existing_files][${fileIndex}][name]`, fileObj.name);
                        }

                        if (fileObj.file && fileObj.file instanceof File) {
                            const file = fileObj.file;
                            formData.append(`assignment_file_${topicIndex}_${assignmentIndex}_${fileIndex}`, file);
                            formData.append(`topics[${topicIndex}][assignments][${assignmentIndex}][files][]`, file);
                        }
                    });
                }
            });
        }
    });

    const submitBtn = $('.btn-next');
    const publishBtn = $('#publishBtn');
    const originalNextText = submitBtn.html();
    const originalPublishText = publishBtn.html();
    const isPublishing = publish;

    // Disable both buttons
    submitBtn.prop('disabled', true);
    publishBtn.prop('disabled', true);

    if (isPublishing) {
        publishBtn.html('<i class="fa-solid fa-spinner fa-spin"></i> Publishing...');
    } else {
        submitBtn.html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
    }

    $.ajax({
        url: form.action,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function (response) {
            const successText = isPublishing ? '<i class="fa-solid fa-clock"></i> Submitted for Review!' : '<i class="fa-solid fa-check"></i> Course Saved!';
            submitBtn.prop('disabled', false).html(successText);
            publishBtn.prop('disabled', false).html(isPublishing ? successText : originalPublishText);
            submitBtn.css('background', isPublishing ? '#3b82f6' : '#10b981');
            if (isPublishing) publishBtn.css('background', '#3b82f6');

            $('#publishBtn').hide();

            $('.course-builder-form .alert-danger').remove();

            const successMessage = isPublishing
                ? 'Course submitted successfully! It is now pending admin approval and will be published once approved.'
                : (response.message || 'Course saved as draft successfully!');

            const successHtml = `
                <div class="alert alert-success" style="background: ${isPublishing ? '#3b82f6' : '#10b981'}; color: white; border: none; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px;">
                    <i class="fa-solid fa-${isPublishing ? 'rocket' : 'check-circle'} me-2"></i>
                    <strong>Success!</strong> ${successMessage}
                </div>
            `;
            $('.course-builder-form').prepend(successHtml);

            $('html, body').animate({ scrollTop: 0 }, 500);

            setTimeout(function () {
                if (response.course && response.course.url) {
                    window.location.href = response.course.url;
                } else {
                    window.location.href = '{{ route("instructor.courses") }}';
                }
            }, 2500);
        },
        error: function (xhr) {
            submitBtn.prop('disabled', false).html(originalNextText);
            publishBtn.prop('disabled', false).html(originalPublishText);

            $('.course-builder-form .alert-success').remove();

            if (xhr.status === 422) {
                const response = xhr.responseJSON;
                let errorHtml = '<div class="alert alert-danger" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px;"><ul class="mb-0" style="padding-left: 20px;">';

                if (response && response.errors) {
                    Object.keys(response.errors).forEach(function (key) {
                        response.errors[key].forEach(function (error) {
                            errorHtml += '<li>' + error + '</li>';
                        });
                    });
                } else if (response && response.message) {
                    errorHtml += '<li>' + response.message + '</li>';
                } else {
                    errorHtml += '<li>Please check the form for errors.</li>';
                }

                errorHtml += '</ul></div>';
                $('.course-builder-form').prepend(errorHtml);
            } else {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'An error occurred while creating the course. Please try again.';

                const errorHtml = `
                    <div class="alert alert-danger" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px;">
                        <i class="fa-solid fa-exclamation-circle me-2"></i>
                        <strong>Error!</strong> ${errorMessage}
                    </div>
                `;
                $('.course-builder-form').prepend(errorHtml);
            }

            $('html, body').animate({ scrollTop: 0 }, 500);
        }
    });
}

function addTopic() {
    const topicId = Date.now();
    const topic = {
        id: topicId,
        title: 'New Topic',
        description: '',
        lessons: [],
        quizzes: [],
        assignments: [],
        items: []
    };
    topics.push(topic);
    renderTopics();
}

function renderTopics() {
    const container = $('#curriculumContainer');

    const expandedStates = {};
    topics.forEach(topic => {
        const topicCard = $(`.topic-card[data-topic-id="${topic.id}"]`);
        if (topicCard.length) {
            const topicBody = topicCard.find(`.topic-body[data-topic-body="${topic.id}"]`);
            expandedStates[topic.id] = topicBody.is(':visible');
        }
    });

    container.empty();

    Object.keys(contentSortableInstances).forEach(key => {
        const stillExists = topics.some(topic => String(topic.id) === key);
        if (!stillExists) {
            const instance = contentSortableInstances[key];
            if (instance && typeof instance.destroy === 'function') {
                instance.destroy();
            }
            delete contentSortableInstances[key];
        }
    });

    topics.forEach((topic, index) => {
        if (!Array.isArray(topic.items)) {
            topic.items = [];
        }

        const topicHtml = `
            <div class="topic-card" data-topic-id="${topic.id}">
                <div class="topic-header">
                    <div class="topic-drag-handle">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </div>
                    <input type="text" class="topic-title-input" value="${topic.title}" placeholder="Topic Title" onchange="updateTopic(${topic.id}, 'title', this.value)">
                    <div class="topic-actions">
                        <button type="button" class="topic-action-btn" onclick="copyTopic(${topic.id})" title="Copy">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <button type="button" class="topic-action-btn" onclick="deleteTopic(${topic.id})" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <button type="button" class="topic-action-btn" onclick="toggleTopic(${topic.id})" title="Collapse">
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                    </div>
                </div>
                <div class="topic-body" data-topic-body="${topic.id}" style="display: ${expandedStates[topic.id] === false ? 'none' : 'block'};">
                    <div class="topic-description">
                        <textarea class="form-control" rows="3" placeholder="Topic description..." onchange="updateTopic(${topic.id}, 'description', this.value)">${topic.description}</textarea>
                    </div>
                    <div class="topic-actions-row">
                        <button type="button" class="btn-add-content" onclick="addLesson(${topic.id})">
                            <i class="fa-solid fa-plus"></i> Lesson
                        </button>
                        <button type="button" class="btn-add-content" onclick="addLiveLesson(${topic.id})" style="background: rgba(229,62,62,0.1); color:#e53e3e; border-color:#e53e3e;">
                            <i class="fa-solid fa-circle" style="font-size:0.6rem;"></i> Live Lesson
                        </button>
                        <button type="button" class="btn-add-content" onclick="addQuiz(${topic.id})">
                            <i class="fa-solid fa-plus"></i> Quiz
                        </button>
                        <button type="button" class="btn-add-content" onclick="addAssignment(${topic.id})">
                            <i class="fa-solid fa-plus"></i> Assignment
                        </button>
                    </div>
                    <div class="topic-content-list" data-content-list="${topic.id}">
                        <!-- Content items will be added here -->
                    </div>
                    <div class="topic-footer">
                        <button type="button" class="btn-ok" onclick="saveTopic(${topic.id})">Ok</button>
                    </div>
                </div>
            </div>
        `;
        container.append(topicHtml);

        renderTopicContent(topic.id);
    });

    initialiseTopicSortable();
}

function updateTopic(topicId, field, value) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        topic[field] = value;
    }
}

function deleteTopic(topicId) {
    topics = topics.filter(t => t.id !== topicId);
    renderTopics();
}

function copyTopic(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        const idMap = {
            lesson: {},
            quiz: {},
            assignment: {}
        };

        const cloneCollection = (collection, typeKey) => {
            return (collection || []).map(item => {
                const newId = Date.now() + Math.random();
                idMap[typeKey][item.id] = newId;
                return {
                    ...item,
                    id: newId,
                    type: typeKey
                };
            });
        };

        const newTopic = {
            id: Date.now(),
            title: `${topic.title} (Copy)`,
            description: topic.description || '',
            lessons: cloneCollection(topic.lessons, 'lesson'),
            quizzes: cloneCollection(topic.quizzes, 'quiz'),
            assignments: cloneCollection(topic.assignments, 'assignment'),
            items: []
        };

        newTopic.items = (topic.items || []).map(ref => {
            const mappedId = idMap[ref.type] ? idMap[ref.type][ref.id] : null;
            if (!mappedId) {
                return null;
            }
            return {
                id: mappedId,
                type: ref.type
            };
        }).filter(Boolean);

        initialiseTopicItemsFromChildren(newTopic);
        topics.push(newTopic);
        renderTopics();
    }
}

function toggleTopic(topicId) {
    $(`.topic-body[data-topic-body="${topicId}"]`).slideToggle();
}

function addLesson(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        const lessonId = Date.now();
        topic.lessons.push({
            id: lessonId,
            title: 'New Lesson',
            type: 'lesson',
            description: '',
            videoType: 'url',
            videoUrl: '',
            videoFile: null,
            duration: '',
            isPreview: false,
            isLive: false,
        });
        topic.items.push({ id: lessonId, type: 'lesson' });
        renderTopicContent(topicId);
        setTimeout(() => toggleContentEdit(topicId, lessonId, 'lesson'), 100);
    }
}

function addLiveLesson(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        const lessonId = Date.now();
        topic.lessons.push({
            id: lessonId,
            title: 'New Live Lesson',
            type: 'lesson',
            description: '',
            videoType: 'live',
            videoUrl: '',
            videoFile: null,
            duration: '',
            isPreview: false,
            isLive: true,
        });
        topic.items.push({ id: lessonId, type: 'lesson' });
        renderTopicContent(topicId);
        setTimeout(() => toggleContentEdit(topicId, lessonId, 'lesson'), 100);
    }
}

function addQuiz(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        const quizId = Date.now();
        topic.quizzes.push({
            id: quizId,
            title: 'New Quiz',
            type: 'quiz',
            description: '',
            questions: [],
            passingScore: 60,
            timeLimit: ''
        });
        topic.items.push({ id: quizId, type: 'quiz' });
        renderTopicContent(topicId);
        setTimeout(() => toggleContentEdit(topicId, quizId, 'quiz'), 100);
    }
}

function addAssignment(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        const assignmentId = Date.now();
        topic.assignments.push({
            id: assignmentId,
            title: 'New Assignment',
            type: 'assignment',
            description: '',
            instructions: '',
            attachments: []
        });
        topic.items.push({ id: assignmentId, type: 'assignment' });
        renderTopicContent(topicId);
        setTimeout(() => toggleContentEdit(topicId, assignmentId, 'assignment'), 100);
    }
}

function renderTopicContent(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const container = $(`.topic-content-list[data-content-list="${topicId}"]`);
    container.empty();

    ensureTopicItems(topic);

    topic.items.forEach(itemReference => {
        const item = findTopicItemByReference(topic, itemReference);
        if (!item) {
            return;
        }

        const icon = item.type === 'lesson' ? 'fa-play-circle' : item.type === 'quiz' ? 'fa-question-circle' : 'fa-file-alt';
        const isExpanded = item.expanded || false;

        let optionsHtml = '';
        if (item.type === 'lesson') {
            const videoType = item.videoType || 'url';
            const isLive = videoType === 'live';
            const liveBadge = isLive ? `<span style="display:inline-block;background:#e53e3e;color:#fff;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:4px;margin-left:8px;letter-spacing:0.5px;vertical-align:middle;">LIVE</span>` : '';
            optionsHtml = `
                <div class="content-item-options" style="display: ${isExpanded ? 'block' : 'none'};">
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Description</label>
                        <textarea class="form-control" rows="3" placeholder="Lesson description..." onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'description', this.value)">${item.description || ''}</textarea>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Video Source</label>
                        <div style="display: flex; gap: 15px; margin-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                                <input type="radio" name="videoType-${item.id}" value="url" ${videoType === 'url' ? 'checked' : ''} onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'videoType', 'url'); renderTopicContent(${topicId});" style="width: 18px; height: 18px; cursor: pointer; margin: 0;">
                                <span>Video URL</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                                <input type="radio" name="videoType-${item.id}" value="upload" ${videoType === 'upload' ? 'checked' : ''} onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'videoType', 'upload'); renderTopicContent(${topicId});" style="width: 18px; height: 18px; cursor: pointer; margin: 0;">
                                <span>Upload Video</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                                <input type="radio" name="videoType-${item.id}" value="live" ${videoType === 'live' ? 'checked' : ''} onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'videoType', 'live'); updateContentItem(${topicId}, ${item.id}, 'lesson', 'isLive', true); renderTopicContent(${topicId});" style="width: 18px; height: 18px; cursor: pointer; margin: 0;">
                                <span style="color:#e53e3e;font-weight:600;">Live Session</span>
                            </label>
                        </div>
                        ${isLive ? `
                            <div style="padding:12px 16px;background:rgba(229,62,62,0.08);border:1px dashed #e53e3e;border-radius:8px;">
                                <p style="margin:0;color:#e53e3e;font-size:13px;font-weight:600;"><i class="fa-solid fa-circle" style="font-size:0.55rem;margin-right:6px;"></i> Live Session Lesson</p>
                                <p style="margin:4px 0 0;color:#555;font-size:12px;">No video upload needed. Schedule a live class from your <a href="/instructor/live-classes" target="_blank">Live Classes</a> page and link it to this lesson.</p>
                            </div>
                        ` : videoType === 'url' ? `
                            <input type="url" class="form-control" placeholder="https://..." value="${item.videoUrl || ''}" onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'videoUrl', this.value)">
                        ` : `
                            <div class="file-upload-area">
                                <input type="file" class="file-upload-input" id="lesson-video-${item.id}" accept="video/*" style="display: none;" onchange="handleVideoUpload(${topicId}, ${item.id}, this.files)">
                                <div class="video-upload-dropzone" onclick="document.getElementById('lesson-video-${item.id}').click()">
                                    <i class="fa-solid fa-video"></i>
                                    <p>Click to upload video file or drag and drop</p>
                                    <small>MP4, AVI, MOV, WEBM (Max 500MB)</small>
                                    ${item.videoFile ? `<div style="margin-top: 10px; color: var(--theme); font-weight: 600;">${item.videoFile.name}</div>` : (item.videoPath ? `<div style="margin-top: 10px; color: var(--theme); font-weight: 600;">Current video uploaded</div>` : '')}
                                </div>
                            </div>
                        `}
                    </div>
                    <div class="form-group">
                        <label>Duration (minutes)</label>
                        <input type="number" class="form-control" placeholder="0" min="0" value="${item.duration || ''}" onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'duration', this.value)">
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" ${item.isPreview ? 'checked' : ''} onchange="updateContentItem(${topicId}, ${item.id}, 'lesson', 'isPreview', this.checked)" style="width: 18px; height: 18px; cursor: pointer; margin: 0;">
                            <span>Preview Lesson (Free)</span>
                        </label>
                    </div>
                </div>
            `;
        } else if (item.type === 'quiz') {
            if (!item.questions) item.questions = [];
            optionsHtml = `
                <div class="content-item-options" style="display: ${isExpanded ? 'block' : 'none'};">
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Description</label>
                        <textarea class="form-control" rows="3" placeholder="Quiz description..." onchange="updateContentItem(${topicId}, ${item.id}, 'quiz', 'description', this.value)">${item.description || ''}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Passing Score (%)</label>
                            <input type="number" class="form-control" placeholder="60" min="0" max="100" value="${item.passingScore || 60}" onchange="updateContentItem(${topicId}, ${item.id}, 'quiz', 'passingScore', this.value)">
                        </div>
                        <div class="form-group">
                            <label>Time Limit (minutes)</label>
                            <input type="number" class="form-control" placeholder="Unlimited" min="0" value="${item.timeLimit || ''}" onchange="updateContentItem(${topicId}, ${item.id}, 'quiz', 'timeLimit', this.value)">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <label style="margin: 0; font-weight: 600;">Questions</label>
                            <button type="button" class="btn-add-question" onclick="addQuestion(${topicId}, ${item.id})" style="padding: 6px 12px; font-size: 13px; background: var(--theme); color: white; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fa-solid fa-plus"></i> Add Question
                            </button>
                        </div>
                        <div class="questions-list">
                            ${item.questions.length === 0 ? '<p style="color: var(--text); opacity: 0.6; font-size: 14px; padding: 10px; text-align: center; background: rgba(108, 112, 111, 0.05); border-radius: 6px;">No questions added yet. Click "Add Question" to get started.</p>' : item.questions.map((q, index) => renderQuestion(q, index + 1, topicId, item.id)).join('')}
                        </div>
                    </div>
                </div>
            `;
        } else if (item.type === 'assignment') {
            if (!item.attachments) item.attachments = [];
            optionsHtml = `
                <div class="content-item-options" style="display: ${isExpanded ? 'block' : 'none'};">
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Description</label>
                        <textarea class="form-control" rows="3" placeholder="Assignment description..." onchange="updateContentItem(${topicId}, ${item.id}, 'assignment', 'description', this.value)">${item.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Instructions</label>
                        <textarea class="form-control" rows="4" placeholder="Detailed instructions for students..." onchange="updateContentItem(${topicId}, ${item.id}, 'assignment', 'instructions', this.value)">${item.instructions || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Assignment Files</label>
                        <div class="file-upload-area">
                            <input type="file" class="file-upload-input" id="assignment-file-${item.id}" multiple style="display: none;" onchange="handleAssignmentFiles(${topicId}, ${item.id}, this.files)">
                            <div class="file-upload-dropzone" onclick="document.getElementById('assignment-file-${item.id}').click()">
                                <i class="fa-solid fa-cloud-upload"></i>
                                <p>Click to upload files or drag and drop</p>
                                <small>PDF, DOC, PPT, ZIP (Max 10MB per file)</small>
                            </div>
                            ${item.attachments.length > 0 ? `
                                <div class="uploaded-files-list" style="margin-top: 12px;">
                                    ${item.attachments.map((file, idx) => `
                                        <div class="uploaded-file-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(108, 112, 111, 0.05); border-radius: 6px; margin-top: 8px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-file" style="color: var(--theme);"></i>
                                                <span style="font-size: 14px; color: var(--header);">${file.name || 'File ' + (idx + 1)}</span>
                                            </div>
                                            <button type="button" onclick="removeAssignmentFile(${topicId}, ${item.id}, ${idx})" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        const itemHtml = `
            <div class="content-item" data-item-id="${item.id}" data-item-type="${item.type}">
                <div class="content-item-header">
                    <i class="fa-solid ${icon}"></i>
                    <input type="text" class="content-item-title" value="${item.title}" placeholder="${item.type} title" onchange="updateContentItem(${topicId}, ${item.id}, '${item.type}', 'title', this.value)">
                    <div class="content-item-actions">
                        <button type="button" class="content-action-btn" onclick="toggleContentEdit(${topicId}, ${item.id}, '${item.type}')" title="${isExpanded ? 'Collapse' : 'Expand'}">
                            <i class="fa-solid fa-${isExpanded ? 'chevron-up' : 'chevron-down'}"></i>
                        </button>
                        <button type="button" class="content-action-btn" onclick="deleteContentItem(${topicId}, ${item.id}, '${item.type}')" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
                ${optionsHtml}
            </div>
        `;
        container.append(itemHtml);
    });

    initialiseContentSortable(topicId);
}

function updateContentItem(topicId, itemId, type, field, value) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    let item;
    if (type === 'lesson') {
        item = topic.lessons.find(l => l.id === itemId);
    } else if (type === 'quiz') {
        item = topic.quizzes.find(q => q.id === itemId);
    } else if (type === 'assignment') {
        item = topic.assignments.find(a => a.id === itemId);
    }

    if (item) {
        item[field] = value;
    }
}

function toggleContentEdit(topicId, itemId, type) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    let item;
    if (type === 'lesson') {
        item = topic.lessons.find(l => l.id === itemId);
    } else if (type === 'quiz') {
        item = topic.quizzes.find(q => q.id === itemId);
    } else if (type === 'assignment') {
        item = topic.assignments.find(a => a.id === itemId);
    }

    if (item) {
        item.expanded = !item.expanded;
        renderTopicContent(topicId);
    }
}

function deleteContentItem(topicId, itemId, type) {
    const topic = topics.find(t => t.id === topicId);
    if (topic) {
        if (type === 'lesson') {
            topic.lessons = topic.lessons.filter(l => l.id !== itemId);
        } else if (type === 'quiz') {
            topic.quizzes = topic.quizzes.filter(q => q.id !== itemId);
        } else {
            topic.assignments = topic.assignments.filter(a => a.id !== itemId);
        }
        topic.items = (topic.items || []).filter(ref => !(ref.id === itemId && ref.type === type));
        renderTopicContent(topicId);
    }
}

function saveTopic(topicId) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const topicCard = $(`.topic-card[data-topic-id="${topicId}"]`);
    const topicTitleInput = topicCard.find('.topic-title-input');
    const topicTitle = topicTitleInput.val() || topic.title;

    if (!topicTitle || topicTitle.trim() === '') {
        alert('Please enter a topic title');
        topicTitleInput.focus();
        return;
    }

    topic.title = topicTitle.trim();

    topicCard.find(`.topic-body[data-topic-body="${topicId}"]`).slideUp(300);

    topicCard.css('border', '2px solid var(--theme)');
    setTimeout(() => {
        topicCard.css('border', '1px solid rgba(108, 112, 111, 0.1)');
    }, 2000);
}

// Quiz Question Functions
function addQuestion(topicId, quizId) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const quiz = topic.quizzes.find(q => q.id === quizId);
    if (!quiz) return;

    if (!quiz.questions) quiz.questions = [];

    const questionId = Date.now();
    quiz.questions.push({
        id: questionId,
        question: '',
        type: 'multiple-choice',
        options: ['', '', '', ''],
        correctAnswer: 0
    });

    quiz.expanded = true;
    renderTopicContent(topicId);
}

function renderQuestion(question, index, topicId, quizId) {
    return `
        <div class="question-item" style="background: rgba(108, 112, 111, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid rgba(108, 112, 111, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong style="color: var(--header);">Question ${index}</strong>
                <button type="button" onclick="deleteQuestion(${topicId}, ${quizId}, ${question.id})" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
            <div class="form-group">
                <label>Question Text</label>
                <input type="text" class="form-control" placeholder="Enter your question..." value="${question.question || ''}" onchange="updateQuestion(${topicId}, ${quizId}, ${question.id}, 'question', this.value)">
            </div>
            <div class="form-group">
                <label>Question Type</label>
                <select class="form-control" onchange="updateQuestionType(${topicId}, ${quizId}, ${question.id}, this.value)">
                    <option value="multiple-choice" ${question.type === 'multiple-choice' ? 'selected' : ''}>Multiple Choice</option>
                    <option value="true-false" ${question.type === 'true-false' ? 'selected' : ''}>True/False</option>
                </select>
            </div>
            ${question.type === 'multiple-choice' || question.type === 'true-false' ? `
                <div class="form-group">
                    <label>Options ${question.type === 'true-false' ? '(Select Correct Answer)' : ''}</label>
                    ${(question.type === 'true-false' ? ['True', 'False'] : (question.options || ['', '', '', ''])).map((opt, optIdx) => {
        if (question.type === 'multiple-choice' && optIdx >= 4) return '';
        return `
                            <div style="display: flex; gap: 10px; margin-bottom: 8px; align-items: center;">
                                <input type="radio" name="correct-${question.id}" value="${optIdx}" ${question.correctAnswer === optIdx ? 'checked' : ''} onchange="updateQuestion(${topicId}, ${quizId}, ${question.id}, 'correctAnswer', ${optIdx})" style="width: auto; cursor: pointer;">
                                <input type="text" class="form-control" placeholder="${question.type === 'true-false' ? opt : 'Option ' + (optIdx + 1)}" value="${opt}" ${question.type === 'true-false' ? 'readonly' : ''} onchange="updateQuestionOption(${topicId}, ${quizId}, ${question.id}, ${optIdx}, this.value)" style="${question.type === 'true-false' ? 'background: #f5f5f5; cursor: not-allowed;' : ''}">
                            </div>
                        `;
    }).join('')}
                </div>
            ` : ''}
        </div>
    `;
}

function deleteQuestion(topicId, quizId, questionId) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const quiz = topic.quizzes.find(q => q.id === quizId);
    if (!quiz || !quiz.questions) return;

    quiz.questions = quiz.questions.filter(q => q.id !== questionId);
    quiz.expanded = true;
    renderTopicContent(topicId);
}

function updateQuestion(topicId, quizId, questionId, field, value) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const quiz = topic.quizzes.find(q => q.id === quizId);
    if (!quiz || !quiz.questions) return;

    const question = quiz.questions.find(q => q.id === questionId);
    if (question) {
        question[field] = value;
    }
}

function updateQuestionType(topicId, quizId, questionId, type) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const quiz = topic.quizzes.find(q => q.id === quizId);
    if (!quiz || !quiz.questions) return;

    const question = quiz.questions.find(q => q.id === questionId);
    if (question) {
        question.type = type;
        if (type === 'true-false') {
            question.options = ['True', 'False'];
            question.correctAnswer = 0;
        } else if (type === 'multiple-choice') {
            if (!question.options || question.options.length < 4) {
                question.options = ['', '', '', ''];
            }
            if (question.correctAnswer >= question.options.length) {
                question.correctAnswer = 0;
            }
        }
        quiz.expanded = true;
        renderTopicContent(topicId);
    }
}

function updateQuestionOption(topicId, quizId, questionId, optionIndex, value) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const quiz = topic.quizzes.find(q => q.id === quizId);
    if (!quiz || !quiz.questions) return;

    const question = quiz.questions.find(q => q.id === questionId);
    if (question && question.options) {
        question.options[optionIndex] = value;
    }
}

// Video Upload Function
function handleVideoUpload(topicId, itemId, files) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const lesson = topic.lessons.find(l => l.id === itemId);
    if (!lesson || !files || files.length === 0) return;

    const videoFile = files[0];

    const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/webm', 'video/x-msvideo'];
    if (!validVideoTypes.includes(videoFile.type) && !videoFile.name.match(/\.(mp4|avi|mov|webm)$/i)) {
        alert('Please upload a valid video file (MP4, AVI, MOV, WEBM)');
        return;
    }

    const maxSize = 500 * 1024 * 1024;
    if (videoFile.size > maxSize) {
        alert('Video file size must be less than 500MB');
        return;
    }

    lesson.videoFile = {
        name: videoFile.name,
        size: videoFile.size,
        file: videoFile
    };

    lesson.expanded = true;
    renderTopicContent(topicId);
}

// Assignment File Functions
function handleAssignmentFiles(topicId, itemId, files) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const assignment = topic.assignments.find(a => a.id === itemId);
    if (!assignment || !files || files.length === 0) return;

    if (!assignment.attachments) assignment.attachments = [];

    Array.from(files).forEach(file => {
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            alert(`${file.name} exceeds the 10MB limit`);
            return;
        }

        assignment.attachments.push({
            id: Date.now() + Math.random(),
            name: file.name,
            size: file.size,
            file: file
        });
    });

    assignment.expanded = true;
    renderTopicContent(topicId);
}

function removeAssignmentFile(topicId, itemId, fileIndex) {
    const topic = topics.find(t => t.id === topicId);
    if (!topic) return;

    const assignment = topic.assignments.find(a => a.id === itemId);
    if (!assignment || !assignment.attachments) return;

    assignment.attachments.splice(fileIndex, 1);
    assignment.expanded = true;
    renderTopicContent(topicId);
}

function initialiseTopicItemsFromChildren(topic) {
    if (!topic) {
        return;
    }

    if (!Array.isArray(topic.items) || topic.items.length === 0) {
        topic.items = [];

        const lessonList = (topic.lessons || []).slice().sort(sortByOrderOrIndex);
        const quizList = (topic.quizzes || []).slice().sort(sortByOrderOrIndex);
        const assignmentList = (topic.assignments || []).slice().sort(sortByOrderOrIndex);

        lessonList.forEach((lesson) => {
            topic.items.push({ id: lesson.id, type: 'lesson' });
        });
        quizList.forEach((quiz) => {
            topic.items.push({ id: quiz.id, type: 'quiz' });
        });
        assignmentList.forEach((assignment) => {
            topic.items.push({ id: assignment.id, type: 'assignment' });
        });
    }

    syncTopicCollections(topic);
}

function ensureTopicItems(topic) {
    if (!topic) return;

    if (!Array.isArray(topic.items)) {
        topic.items = [];
    }

    const appendIfMissing = (id, type) => {
        if (!topic.items.find(ref => ref.id === id && ref.type === type)) {
            topic.items.push({ id, type });
        }
    };

    (topic.lessons || []).forEach(lesson => appendIfMissing(lesson.id, 'lesson'));
    (topic.quizzes || []).forEach(quiz => appendIfMissing(quiz.id, 'quiz'));
    (topic.assignments || []).forEach(assignment => appendIfMissing(assignment.id, 'assignment'));

    topic.items = topic.items.filter(ref => {
        return Boolean(findTopicItemByReference(topic, ref));
    });

    syncTopicCollections(topic);
}

function findTopicItemByReference(topic, reference) {
    if (!reference || !topic) return null;

    if (reference.type === 'lesson') {
        const found = topic.lessons.find(item => item.id === reference.id);
        if (found) {
            found.type = 'lesson';
        }
        return found || null;
    }

    if (reference.type === 'quiz') {
        const found = topic.quizzes.find(item => item.id === reference.id);
        if (found) {
            found.type = 'quiz';
        }
        return found || null;
    }

    if (reference.type === 'assignment') {
        const found = topic.assignments.find(item => item.id === reference.id);
        if (found) {
            found.type = 'assignment';
        }
        return found || null;
    }

    return null;
}

function sortByOrderOrIndex(a, b) {
    const orderA = typeof a.order !== 'undefined' ? a.order : 0;
    const orderB = typeof b.order !== 'undefined' ? b.order : 0;
    if (orderA === orderB) {
        return 0;
    }
    return orderA < orderB ? -1 : 1;
}

function initialiseTopicSortable() {
    const container = document.getElementById('curriculumContainer');
    if (!container || typeof Sortable === 'undefined') {
        return;
    }

    if (topicSortableInstance) {
        topicSortableInstance.destroy();
    }

    topicSortableInstance = Sortable.create(container, {
        handle: '.topic-drag-handle',
        animation: 200,
        onEnd: function (evt) {
            if (evt.oldIndex === evt.newIndex) {
                return;
            }
            const movedTopic = topics.splice(evt.oldIndex, 1)[0];
            topics.splice(evt.newIndex, 0, movedTopic);
            renderTopics();
        }
    });
}

function initialiseContentSortable(topicId) {
    if (typeof Sortable === 'undefined') {
        return;
    }

    const container = document.querySelector(`.topic-content-list[data-content-list="${topicId}"]`);
    if (!container) {
        return;
    }

    if (contentSortableInstances[topicId]) {
        const existing = contentSortableInstances[topicId];
        if (existing && typeof existing.destroy === 'function') {
            existing.destroy();
        }
    }

    contentSortableInstances[topicId] = Sortable.create(container, {
        handle: '.content-item-header .fa-grip-vertical',
        animation: 200,
        onEnd: function (evt) {
            if (evt.oldIndex === evt.newIndex) {
                return;
            }

            const topic = topics.find(t => t.id === topicId);
            if (!topic || !Array.isArray(topic.items)) {
                return;
            }

            const movedItem = topic.items.splice(evt.oldIndex, 1)[0];
            topic.items.splice(evt.newIndex, 0, movedItem);
            syncTopicCollections(topic);
        }
    });
}

function syncTopicCollections(topic) {
    if (!topic) return;

    const lessonMap = new Map((topic.lessons || []).map(item => [item.id, item]));
    const quizMap = new Map((topic.quizzes || []).map(item => [item.id, item]));
    const assignmentMap = new Map((topic.assignments || []).map(item => [item.id, item]));

    const newLessons = [];
    const newQuizzes = [];
    const newAssignments = [];

    (topic.items || []).forEach(ref => {
        if (ref.type === 'lesson' && lessonMap.has(ref.id)) {
            const lesson = lessonMap.get(ref.id);
            lesson.order = newLessons.length;
            newLessons.push(lesson);
            lessonMap.delete(ref.id);
        } else if (ref.type === 'quiz' && quizMap.has(ref.id)) {
            const quiz = quizMap.get(ref.id);
            quiz.order = newQuizzes.length;
            newQuizzes.push(quiz);
            quizMap.delete(ref.id);
        } else if (ref.type === 'assignment' && assignmentMap.has(ref.id)) {
            const assignment = assignmentMap.get(ref.id);
            assignment.order = newAssignments.length;
            newAssignments.push(assignment);
            assignmentMap.delete(ref.id);
        }
    });

    lessonMap.forEach(value => {
        value.order = newLessons.length;
        newLessons.push(value);
    });
    quizMap.forEach(value => {
        value.order = newQuizzes.length;
        newQuizzes.push(value);
    });
    assignmentMap.forEach(value => {
        value.order = newAssignments.length;
        newAssignments.push(value);
    });

    topic.lessons = newLessons;
    topic.quizzes = newQuizzes;
    topic.assignments = newAssignments;
}