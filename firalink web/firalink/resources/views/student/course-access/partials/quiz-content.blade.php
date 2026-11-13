<div class="quiz-content">
    <div class="mhq-courses-details-area">
        <div class="mhq-courses-details-wrapper">
            <div class="mhq-left-content">
                <!-- Quiz Header Section -->
                <div class="quiz-header-section mb-4">
                    <div class="quiz-header-content">
                        <h2 class="quiz-header-title">{{ $quiz->title }}</h2>
                        @if ($quiz->description)
                            <p class="quiz-header-description">{!! strip_tags($quiz->description) !!}</p>
                        @endif
                    </div>
                    <div class="quiz-header-decoration-1"></div>
                    <div class="quiz-header-decoration-2"></div>
                </div>

                <!-- Quiz Info Cards -->
                <div class="quiz-info-cards mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="quiz-info-card">
                                <div class="quiz-info-icon-wrapper">
                                    <i class="fas fa-trophy quiz-info-icon"></i>
                                </div>
                                <h6 class="quiz-info-label">{{ __('student.passing_score') }}</h6>
                                <h3 class="quiz-info-value">{{ $quiz->passing_score }}%</h3>
                            </div>
                        </div>
                        @if ($quiz->time_limit)
                            <div class="col-md-4">
                                <div class="quiz-info-card theme-border timer-card">
                                    <div class="quiz-info-icon-wrapper theme-bg">
                                        <i class="fas fa-clock quiz-info-icon theme-color"></i>
                                    </div>
                                    <h6 class="quiz-info-label">{{ __('student.time_limit') }}</h6>
                                    <h3 class="quiz-info-value">
                                        {{ str_pad($quiz->time_limit, 2, '0', STR_PAD_LEFT) }}:00</h3>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="quiz-info-card">
                                <div class="quiz-info-icon-wrapper">
                                    <i class="fas fa-question-circle quiz-info-icon"></i>
                                </div>
                                <h6 class="quiz-info-label">{{ __('student.total_questions') }}</h6>
                                <h3 class="quiz-info-value">{{ $quiz->questions->count() }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Previous Attempt Alert -->
                @if ($previousAttempt)
                    <div class="previous-attempt-alert mb-4">
                        <div class="previous-attempt-content">
                            <div class="previous-attempt-info">
                                <h5 class="previous-attempt-title">
                                    <i class="fas fa-history previous-attempt-title-icon"></i> {{ __('student.previous_attempt') }}
                                </h5>
                                <div class="previous-attempt-details">
                                    <div>
                                        <strong class="previous-attempt-score-label">{{ __('student.score_label') }}</strong>
                                        <span class="previous-attempt-score-value">{{ $previousAttempt->score }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="previous-attempt-actions">
                                <button class="theme-btn view-results-btn" data-attempt-id="{{ $previousAttempt->id }}">
                                    {{ __('student.view_results') }} <i class="fas fa-eye"></i>
                                </button>
                                <div class="previous-attempt-badge">
                                    @if ($previousAttempt->passed)
                                        <span class="badge badge-passed">
                                            <i class="fas fa-check-circle me-2"></i>{{ __('student.passed') }}
                                        </span>
                                    @else
                                        <span class="badge badge-failed">
                                            <i class="fas fa-times-circle me-2"></i>{{ __('student.failed') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif


                <!-- Quiz Form -->
                <form id="quiz-form" data-quiz-id="{{ $quiz->id }}" data-course-id="{{ $quiz->topic->course_id }}">
                    <input type="hidden" name="time_taken" id="time-taken" value="0">

                    <!-- Questions -->
                    <div class="questions-container">
                        @foreach ($quiz->questions as $index => $question)
                            <div class="question-card mb-4">
                                <!-- Question Number Badge -->
                                <div class="question-number-badge">
                                    {{ $index + 1 }}
                                </div>

                                <!-- Question Header -->
                                <div class="question-header">
                                    <h4 class="question-title">
                                        {{ $question->question }}
                                    </h4>
                                    @if ($question->type === 'true-false')
                                        <span class="badge question-type-badge theme-2">
                                            {{ __('student.true_false_question') }}
                                        </span>
                                    @else
                                        <span class="badge question-type-badge theme">
                                            {{ __('student.multiple_choice') }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Options -->
                                <div class="options-container">
                                    @foreach ($question->options as $optionIndex => $option)
                                        <div class="quiz-option-item">
                                            <input type="radio" name="answers[{{ $question->id }}][selected_answer]"
                                                value="{{ $optionIndex }}"
                                                id="question-{{ $question->id }}-option-{{ $optionIndex }}"
                                                class="quiz-radio-input">
                                            <label for="question-{{ $question->id }}-option-{{ $optionIndex }}"
                                                class="quiz-option-label">
                                                <span class="quiz-option-radio">
                                                    <span class="quiz-option-radio-dot"></span>
                                                </span>
                                                <span class="quiz-option-text">{{ $option }}</span>
                                                <span class="quiz-option-check">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            </label>
                                            <input type="hidden" name="answers[{{ $question->id }}][question_id]"
                                                value="{{ $question->id }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Submit Button -->
                    <div class="quiz-submit-section">
                        <button type="submit" class="theme-btn quiz-submit-btn">
                            <i class="fas fa-paper-plane"></i> {{ __('student.submit_quiz') }}
                        </button>
                        <p class="quiz-submit-info">
                            <i class="fas fa-info-circle me-2"></i>{{ __('student.review_before_submit') }}
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($quiz->time_limit)
        <!-- Floating Timer -->
        <div id="quiz-timer" class="floating-quiz-timer" data-time-limit="{{ $quiz->time_limit }}">
            <div class="floating-timer-content">
                <i class="fas fa-clock floating-timer-icon"></i>
                <span class="floating-timer-text">{{ str_pad($quiz->time_limit, 2, '0', STR_PAD_LEFT) }}:00</span>
            </div>
        </div>
    @endif
</div>
