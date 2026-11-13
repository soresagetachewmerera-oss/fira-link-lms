@extends('layouts.instructor')

@section('content')
    <section class="section-bg fix">
        <div class="container">
            <div class="course-builder-layout">
                <div class="course-builder-main">
                    <!-- Course Builder Header -->
                    <div class="course-builder-header">
                        <div class="builder-title">
                            <i class="fa-solid fa-arrow-left" onclick="window.history.back()"
                                style="cursor: pointer; margin-right: 15px;"></i>
                            <h2>
                                @isset($course)
                                    Edit Course
                                @else
                                    Course Builder
                                @endisset
                            </h2>
                        </div>
                        <div class="builder-progress">
                            <div class="progress-step active" data-step="1">
                                <span class="step-number">1</span>
                                <span class="step-label">Basics</span>
                            </div>
                            <div class="progress-step" data-step="2">
                                <span class="step-number">2</span>
                                <span class="step-label">Curriculum</span>
                            </div>
                            <div class="progress-step" data-step="3">
                                <span class="step-number">3</span>
                                <span class="step-label">Additional</span>
                            </div>
                        </div>
                        <div class="builder-actions">

                            <button type="button" class="btn-publish" id="publishBtn" style="display: none;">
                                <i class="fa-solid fa-rocket"></i>
                                Publish
                            </button>
                        </div>
                    </div>

                    <form id="courseBuilderForm" class="course-builder-form" method="POST"
                        action="@isset($course){{ route('instructor.courses.update', $course->id) }}@else{{ route('instructor.courses.store') }}@endisset"
                        enctype="multipart/form-data">
                        @csrf
                        @isset($course)
                            @method('PUT')
                        @endisset

                        <!-- Step 1: Basics -->
                        <div class="builder-step active" data-step="1">
                            <div class="step-content">
                                <div class="step-left">
                                    <!-- Title -->
                                    <div class="form-group">
                                        <label>
                                            Title
                                            <i class="fa-solid fa-sparkles" style="color: #FFA41B; margin-left: 5px;"></i>
                                        </label>
                                        <input type="text" name="title" class="form-control"
                                            placeholder="Enter course title"
                                            value="@isset($course){{ old('title', $course->title) }}@else{{ old('title') }}@endisset"
                                            required>
                                    </div>

                                    <!-- Description -->
                                    <div class="form-group">
                                        <label>
                                            Description
                                            <i class="fa-solid fa-sparkles" style="color: #FFA41B; margin-left: 5px;"></i>
                                            <button type="button" class="btn btn-sm btn-outline-primary ml-2"
                                                id="generateDescriptionBtn" style="font-size: 0.7rem; padding: 2px 8px;">
                                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate with AI
                                            </button>
                                        </label>
                                        <div class="rich-editor-toolbar">
                                            <div class="toolbar-group">
                                                <select class="format-select">
                                                    <option value="Paragraph">Paragraph</option>
                                                    <option value="Heading 1">Heading 1</option>
                                                    <option value="Heading 2">Heading 2</option>
                                                    <option value="Heading 3">Heading 3</option>
                                                </select>
                                                <button type="button" class="toolbar-btn" data-command="bold"
                                                    title="Bold"><i class="fa-solid fa-bold"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="italic"
                                                    title="Italic"><i class="fa-solid fa-italic"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="underline"
                                                    title="Underline"><i class="fa-solid fa-underline"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="link"
                                                    title="Link"><i class="fa-solid fa-link"></i></button>
                                            </div>
                                            <div class="toolbar-group">
                                                <button type="button" class="toolbar-btn" data-command="align" data-value=""
                                                    title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="align"
                                                    data-value="center" title="Align Center"><i
                                                        class="fa-solid fa-align-center"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="align"
                                                    data-value="right" title="Align Right"><i
                                                        class="fa-solid fa-align-right"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="align"
                                                    data-value="justify" title="Justify"><i
                                                        class="fa-solid fa-align-justify"></i></button>
                                            </div>
                                            <div class="toolbar-group">
                                                <button type="button" class="toolbar-btn" data-command="list"
                                                    data-value="bullet" title="Bullet List"><i
                                                        class="fa-solid fa-list-ul"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="list"
                                                    data-value="ordered" title="Numbered List"><i
                                                        class="fa-solid fa-list-ol"></i></button>

                                            </div>
                                        </div>
                                        <div class="rich-editor-wrapper">
                                            <div class="rich-editor"></div>
                                            <textarea name="description" class="rich-editor-textarea"
                                                style="display: none;"></textarea>
                                        </div>

                                    </div>

                                    <!-- Options Tabs -->
                                    <div class="options-tabs">
                                        <div class="tab-header">
                                            <button type="button" class="tab-btn active" data-tab="general">
                                                <i class="fa-solid fa-gear"></i>
                                                General
                                            </button>
                                        </div>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="general-tab">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label>Maximum Student</label>
                                                        <input type="number" name="max_students" id="max_students"
                                                            class="form-control"
                                                            value="@isset($course){{ old('max_students', $course->max_students) }}@else{{ old('max_students', '20') }}@endisset"
                                                            min="1">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Difficulty Level</label>
                                                        <select name="difficulty" class="form-control">
                                                            <option value="beginner"
                                                                @isset($course){{ old('difficulty', $course->difficulty) === 'beginner' ? 'selected' : '' }}@else{{ old('difficulty') === 'beginner' ? 'selected' : '' }}@endisset>
                                                                Beginner</option>
                                                            <option value="intermediate"
                                                                @isset($course){{ old('difficulty', $course->difficulty) === 'intermediate' ? 'selected' : '' }}@else{{ old('difficulty') === 'intermediate' ? 'selected' : '' }}@endisset>
                                                                Intermediate</option>
                                                            <option value="advanced"
                                                                @isset($course){{ old('difficulty', $course->difficulty) === 'advanced' ? 'selected' : '' }}@else{{ old('difficulty') === 'advanced' ? 'selected' : '' }}@endisset>
                                                                Advanced</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="custom-control custom-control--checkbox">
                                                        <input type="checkbox" name="public_course" id="public_course"
                                                            @isset($course){{ old('public_course', $course->public_course) ? 'checked' : '' }}@else{{ old('public_course') ? 'checked' : '' }}@endisset>
                                                        <span class="control-indicator"></span>
                                                        <span class="control-label">Public Course</span>
                                                    </label>
                                                </div>
                                                <div class="form-group mt-4">
                                                    <label class="custom-control custom-control--checkbox">
                                                        <input type="checkbox" name="is_live_course" id="is_live_course"
                                                            @isset($course){{ old('is_live_course', $course->is_live_course) ? 'checked' : '' }}@else{{ old('is_live_course') ? 'checked' : '' }}@endisset>
                                                        <span class="control-indicator"></span>
                                                        <span class="control-label">
                                                            Live Course
                                                            <span style="display:inline-block;background:#e53e3e;color:#fff;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:4px;margin-left:5px;letter-spacing:0.5px;vertical-align:middle;">LIVE</span>
                                                        </span>
                                                    </label>
                                                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Show a Live badge on course cards in the frontend and app.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="step-right">
                                    <!-- Visibility -->
                                    <div class="form-group">
                                        <label>Visibility</label>
                                        <select name="visibility" class="form-control">
                                            <option value="public"
                                                @isset($course){{ old('visibility', $course->visibility) === 'public' ? 'selected' : '' }}@else{{ old('visibility') === 'public' ? 'selected' : '' }}@endisset>
                                                <i class="fa-solid fa-eye"></i> Public
                                            </option>
                                            <option value="private"
                                                @isset($course){{ old('visibility', $course->visibility) === 'private' ? 'selected' : '' }}@else{{ old('visibility') === 'private' ? 'selected' : '' }}@endisset>
                                                Private</option>
                                            <option value="draft"
                                                @isset($course){{ old('visibility', $course->visibility) === 'draft' ? 'selected' : '' }}@else{{ old('visibility') === 'draft' ? 'selected' : '' }}@endisset>
                                                Draft</option>
                                        </select>
                                    </div>

                                    <!-- Schedule -->
                                    <div class="form-group">
                                        <label class="custom-control custom-control--checkbox">
                                            <input type="checkbox" name="schedule_enabled" id="schedule_enabled"
                                                @isset($course){{ old('schedule_enabled', $course->schedule_enabled) ? 'checked' : '' }}@else{{ old('schedule_enabled') ? 'checked' : '' }}@endisset>
                                            <span class="control-indicator"></span>
                                            <span class="control-label">Schedule</span>
                                        </label>
                                        @php
                                            $scheduleDisplayStyle = 'none';
                                            $scheduleDateValue = old('schedule_date', '');
                                            if (isset($course)) {
                                                $scheduleEnabled = old('schedule_enabled', $course->schedule_enabled);
                                                $scheduleDisplayStyle = $scheduleEnabled ? 'block' : 'none';
                                                if ($course->schedule_date) {
                                                    $scheduleDateValue = old(
                                                        'schedule_date',
                                                        $course->schedule_date->format('Y-m-d\TH:i'),
                                                    );
                                                }
                                            } else {
                                                $scheduleEnabled = old('schedule_enabled', false);
                                                $scheduleDisplayStyle = $scheduleEnabled ? 'block' : 'none';
                                            }
                                        @endphp
                                        <div id="schedule-date-picker"
                                            style="display: {{ $scheduleDisplayStyle }}; margin-top: 12px;">
                                            <label
                                                style="font-size: 14px; font-weight: 600; color: var(--header); margin-bottom: 8px; display: block;">Select
                                                Date & Time</label>
                                            <input type="datetime-local" name="schedule_date" id="schedule_date"
                                                class="form-control" style="width: 100%;" value="{{ $scheduleDateValue }}">
                                        </div>
                                    </div>

                                    <!-- Featured Image -->
                                    <div class="form-group">
                                        <label>
                                            Featured Image
                                            <i class="fa-solid fa-sparkles" style="color: #FFA41B; margin-left: 5px;"></i>
                                        </label>
                                        <div class="image-upload-area">
                                            <input type="file" name="featured_image" id="featuredImage" accept="image/*"
                                                style="display: none;">
                                            <div class="image-preview" id="featuredImagePreview">
                                                <i class="fa-solid fa-image"></i>
                                                <span>Click to upload</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Intro Video -->
                                    <div class="form-group">
                                        <label>Intro Video</label>
                                        <div class="video-upload-area">
                                            <input type="file" name="intro_video" id="introVideo" accept="video/*"
                                                style="display: none;">
                                            <div class="video-preview" id="introVideoPreview">
                                                <i class="fa-solid fa-video"></i>
                                                <span>Upload video</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing Model -->
                                    <div class="form-group">
                                        <label>Pricing Model</label>
                                        <div class="pricing-options">
                                            <label class="custom-control custom-control--radio">
                                                <input type="radio" name="pricing_model" value="free"
                                                    @isset($course){{ old('pricing_model', $course->pricing_model) === 'free' ? 'checked' : '' }}@else{{ old('pricing_model', 'free') === 'free' ? 'checked' : '' }}@endisset>
                                                <span class="control-indicator"></span>
                                                <span class="control-label">Free</span>
                                            </label>
                                            <label class="custom-control custom-control--radio">
                                                <input type="radio" name="pricing_model" value="paid"
                                                    @isset($course){{ old('pricing_model', $course->pricing_model) === 'paid' ? 'checked' : '' }}@else{{ old('pricing_model') === 'paid' ? 'checked' : '' }}@endisset>
                                                <span class="control-indicator"></span>
                                                <span class="control-label">Paid</span>
                                            </label>
                                        </div>
                                        <div class="pricing-fields" id="pricingFields"
                                            style="display: @isset($course){{ old('pricing_model', $course->pricing_model) === 'paid' ? 'block' : 'none' }}@else{{ old('pricing_model') === 'paid' ? 'block' : 'none' }}@endisset;">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Regular Price</label>
                                                    <div class="price-input-wrapper">
                                                        <span class="currency">$</span>
                                                        <input type="number" name="regular_price" class="form-control"
                                                            step="0.01" min="0" placeholder="0.00"
                                                            value="@isset($course){{ old('regular_price', $course->regular_price) }}@else{{ old('regular_price') }}@endisset">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sale Price</label>
                                                    <div class="price-input-wrapper">
                                                        <span class="currency">$</span>
                                                        <input type="number" name="sale_price" class="form-control"
                                                            step="0.01" min="0" placeholder="0.00"
                                                            value="@isset($course){{ old('sale_price', $course->sale_price) }}@else{{ old('sale_price') }}@endisset">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Curriculum -->
                        <div class="builder-step" data-step="2">
                            <div class="step-header">
                                <i class="fa-solid fa-arrow-left" onclick="changeStep(1)"
                                    style="cursor: pointer; margin-right: 10px;"></i>
                                <h3>Curriculum</h3>
                            </div>

                            <div class="curriculum-container" id="curriculumContainer">
                                <!-- Topics will be added here dynamically -->
                            </div>

                            <button type="button" class="btn-add-topic" onclick="addTopic()">
                                <i class="fa-solid fa-plus"></i>
                                Add Topic
                            </button>
                        </div>

                        <!-- Step 3: Additional -->
                        <div class="builder-step" data-step="3">
                            <div class="step-header">
                                <i class="fa-solid fa-arrow-left" onclick="changeStep(2)"
                                    style="cursor: pointer; margin-right: 10px;"></i>
                                <h3>Additional Settings</h3>
                            </div>

                            <div class="additional-settings">
                                <div class="form-group">
                                    <label>Course Tags</label>
                                    <input type="text" name="tags" class="form-control"
                                        placeholder="Enter tags separated by commas"
                                        value="@isset($course){{ old('tags', $course->tags) }}@else{{ old('tags') }}@endisset">
                                    <small class="form-hint">Separate tags with commas</small>
                                </div>

                                <div class="form-group">
                                    <label>Course Category</label>
                                    <select name="course_category_id" id="course_category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        @foreach ($categoryOptions as $option)
                                            <option value="{{ $option->id }}"
                                                {{ (string) $selectedCategoryId === (string) $option->id ? 'selected' : '' }}>
                                                {{ $option->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($selectedCategoryLabel && !$selectedCategoryId)
                                        <small class="text-muted d-block mt-1">Current:
                                            {{ $selectedCategoryLabel }}</small>
                                    @endif
                                    @if ($categoryOptions->isEmpty())
                                        <small class="text-muted d-block mt-1">No categories available yet. Please contact
                                            the administrator.</small>
                                    @endif
                                    <input type="hidden" name="category_label" id="category_label"
                                        value="{{ $selectedCategoryLabel }}">
                                </div>

                                <div class="form-group">
                                    <label>Course Language</label>
                                    <select name="course_language_id" id="course_language_id" class="form-control">
                                        <option value="">Select Language</option>
                                        @foreach ($languageOptions as $option)
                                            <option value="{{ $option->id }}"
                                                {{ (string) $selectedLanguageId === (string) $option->id ? 'selected' : '' }}>
                                                {{ $option->name }}@if ($option->native_name)
                                                    ({{ $option->native_name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($selectedLanguageLabel && !$selectedLanguageId)
                                        <small class="text-muted d-block mt-1">Current:
                                            {{ $selectedLanguageLabel }}</small>
                                    @endif
                                    @if ($languageOptions->isEmpty())
                                        <small class="text-muted d-block mt-1">No languages available yet. Please contact
                                            the administrator.</small>
                                    @endif
                                    <input type="hidden" name="language_label" id="language_label"
                                        value="{{ $selectedLanguageLabel }}">
                                </div>

                                <div class="form-group">
                                    <label>Course Requirements</label>
                                    <textarea name="requirements" class="form-control" rows="4"
                                        placeholder="List any prerequisites or requirements...">
                            @isset($course)
                            {{ old('requirements', $course->requirements) }}@else{{ old('requirements') }}
                            @endisset
                            </textarea>
                                </div>

                                <div class="form-group">
                                    <label>Learning Objectives</label>
                                    <textarea name="objectives" class="form-control" rows="4"
                                        placeholder="What will students learn?">
                            @isset($course)
                            {{ old('objectives', $course->objectives) }}@else{{ old('objectives') }}
                            @endisset
                            </textarea>
                                    <small class="form-hint">Separate objectives with new lines</small>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="builder-navigation">
                            <button type="button" class="btn-nav btn-prev" onclick="previousStep()" style="display: none;">
                                <i class="fa-solid fa-chevron-left"></i>
                                Previous
                            </button>
                            <button type="button" class="btn-nav btn-next" onclick="nextStep()">
                                Next
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            .rich-editor-wrapper {
                position: relative;
            }

            .rich-editor-loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(229, 70, 192, 0.15);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 10;
                backdrop-filter: blur(2px);
                border-radius: 8px;
            }

            .ai-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #FFA41B;
                /* Using the theme secondary color */
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-bottom: 15px;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            .ai-loading-text {
                color: #333;
                font-weight: 500;
                font-size: 14px;
                animation: pulse 1.5s infinite;
            }

            @keyframes pulse {
                0% {
                    opacity: 0.6;
                }

                50% {
                    opacity: 1;
                }

                100% {
                    opacity: 0.6;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/front/js/quill.js') }}"></script>
        <link href="{{ asset('assets/front/css/quill.snow.css') }}" rel="stylesheet">
        <script src="{{ asset('assets/front/js/sortable.min.js') }}"></script>
        @isset($courseDataArray)
            <script>
                const courseData = {!! json_encode($courseDataArray) !!};
            </script>
        @endisset
        <script src="{{ asset('assets/front/js/instructor/course-builder.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const categorySelect = document.getElementById('course_category_id');
                const categoryLabel = document.getElementById('category_label');
                const languageSelect = document.getElementById('course_language_id');
                const languageLabel = document.getElementById('language_label');

                if (categorySelect && categoryLabel) {
                    categorySelect.addEventListener('change', function () {
                        const selected = this.selectedOptions[0];
                        categoryLabel.value = selected ? selected.text.trim() : '';
                    });
                }

                if (languageSelect && languageLabel) {
                    languageSelect.addEventListener('change', function () {
                        const selected = this.selectedOptions[0];
                        languageLabel.value = selected ? selected.text.trim() : '';
                    });
                }

                // AI Description Generation
                const generateBtn = document.getElementById('generateDescriptionBtn');
                if (generateBtn) {
                    generateBtn.addEventListener('click', function () {
                        const titleInput = document.querySelector('input[name="title"]');
                        const title = titleInput.value.trim();

                        if (!title) {
                            alert('Please enter a course title first.');
                            titleInput.focus();
                            return;
                        }

                        // Show button loading state
                        const originalText = generateBtn.innerHTML;
                        generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
                        generateBtn.disabled = true;

                        // Show editor loading overlay
                        const editorWrapper = document.querySelector('.rich-editor-wrapper');
                        let overlay = editorWrapper.querySelector('.rich-editor-loading-overlay');
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.className = 'rich-editor-loading-overlay';
                            overlay.innerHTML = `
                                                                        <div class="ai-spinner"></div>
                                                                        <div class="ai-loading-text">Generating Description with AI...</div>
                                                                    `;
                            editorWrapper.appendChild(overlay);
                        }
                        overlay.style.display = 'flex';

                        fetch('{{ route('instructor.courses.generate-description') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ title: title })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update Quill editor
                                    if (window.quillEditor) {
                                        // Use Quill API to set content
                                        window.quillEditor.clipboard.dangerouslyPasteHTML(data.description);
                                    } else {
                                        // Fallback to DOM manipulation
                                        const quill = document.querySelector('.ql-editor');
                                        if (quill) {
                                            quill.innerHTML = data.description;
                                        }
                                    }

                                    // Update textarea
                                    const textarea = document.querySelector('textarea[name="description"]');
                                    if (textarea) {
                                        textarea.value = data.description;
                                    }
                                } else {
                                    alert(data.message || 'Failed to generate description.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while generating the description.');
                            })
                            .finally(() => {
                                // Reset button
                                generateBtn.innerHTML = originalText;
                                generateBtn.disabled = false;
                                // Hide overlay
                                if (overlay) {
                                    overlay.style.display = 'none';
                                }
                            });
                    });
                }
            });
        </script>
    @endpush
@endsection