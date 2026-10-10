<div class="assignment-content">
    <div class="mhq-courses-details-area">
        <div class="mhq-courses-details-wrapper">
            <div class="mhq-left-content">
                <h3 class="mb-4">{{ $assignment->title }}</h3>

                @if ($assignment->description)
                    <div class="assignment-description mb-4">
                        {!! $assignment->description !!}
                    </div>
                @endif

                @if ($assignment->instructions)
                    <div class="assignment-instructions mb-4">
                        <h5 class="assignment-instructions-title">
                            <i class="fas fa-list-check assignment-instructions-icon"></i> {{ __('student.instructions') }}
                        </h5>
                        <div class="assignment-instructions-text">
                            {!! $assignment->instructions !!}
                        </div>
                    </div>
                @endif

                @if ($assignment->files->count() > 0)
                    <div class="assignment-files mb-4">
                        <h5 class="assignment-files-title">
                            <i class="fas fa-paperclip assignment-files-icon"></i> {{ __('student.attachments') }}
                        </h5>
                        <ul class="list-group" style="border: none;">
                            @foreach ($assignment->files as $file)
                                <li class="assignment-file-item">
                                    <a href="{{ asset('storage/' . $file->file_path) }}"
                                        download="{{ $file->original_filename }}"
                                        class="assignment-file-link text-decoration-none">
                                        <i class="fas fa-file-download"></i> {{ $file->original_filename }}
                                    </a>
                                    <span class="assignment-file-size">({{ $file->file_size ?? __('student.unknown_size') }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($submission)
                    <div class="submission-status mb-4">
                        <div
                            class="submission-status-box submission-status-{{ $submissionData['status_class'] ?? 'pending' }}">
                            <h5 class="submission-status-title">
                                {{ __('student.submission_status') }}: <span
                                    class="submission-status-text">{{ $submissionData['status_label'] ?? __('student.pending') }}</span>
                            </h5>
                            @if (!empty($submissionData['is_graded']))
                                <p style="margin-bottom: 10px;">
                                    <strong>{{ __('student.grade_label') }}:</strong>
                                    <span class="submission-grade-value">{{ $submission->grade ?? 'N/A' }}%</span>
                                </p>
                            @endif
                            @if ($submission->instructor_notes)
                                <p style="margin-bottom: 10px;">
                                    <strong>{{ __('student.instructor_notes') }}:</strong>
                                    <span style="color: var(--text);">{{ $submission->instructor_notes }}</span>
                                </p>
                            @endif
                            <p style="margin-bottom: 0;">
                                <strong>{{ __('student.submitted_label') }}:</strong>
                                <span style="color: var(--text);">{{ $submissionData['submitted_at'] ?? '—' }}</span>
                            </p>
                        </div>

                        @if ($submission->submission_text)
                            <div class="submission-text-box mb-4">
                                <h5 style="color: var(--header); margin-bottom: 15px;">{{ __('student.your_submission') }}</h5>
                                <div class="submission-text-content">
                                    {!! $submission->submission_text !!}
                                </div>
                            </div>
                        @endif

                        @if ($submission->files->count() > 0)
                            <div class="submission-files-box">
                                <h5 style="color: var(--header); margin-bottom: 20px;">{{ __('student.submitted_files') }}</h5>
                                <ul class="list-group" style="border: none;">
                                    @foreach ($submission->files as $file)
                                        <li class="submission-file-item">
                                            <a href="{{ $file->file_url }}" download="{{ $file->original_filename }}"
                                                class="submission-file-link text-decoration-none">
                                                <i class="fas fa-file-download"></i> {{ $file->original_filename }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    @if (!empty($submissionData['can_resubmit']))
                        <div class="assignment-form-resubmit mb-4">
                            <div class="assignment-form mb-3"
                                style="background: rgba(0, 79, 68, 0.05); padding: 20px; border-radius: 12px;">
                                <h5 style="margin-bottom: 10px; color: var(--header);">{{ __('student.ready_to_try_again') }}</h5>
                                <p style="margin: 0; color: var(--text);">{{ __('student.resubmit_desc') }}</p>
                            </div>

                            <form id="assignment-form" enctype="multipart/form-data"
                                data-assignment-id="{{ $assignment->id }}"
                                data-course-id="{{ $assignment->topic->course_id }}" class="assignment-form">
                                <div class="mb-4">
                                    <label for="submission_text" class="form-label assignment-form-label">{{ __('student.submission_text_optional') }}</label>
                                    <textarea class="form-control assignment-form-textarea" id="submission_text" name="submission_text" rows="10"></textarea>
                                </div>

                                <div class="mb-4">
                                    <label for="files" class="form-label assignment-form-label">{{ __('student.upload_files_optional') }}</label>
                                    <input type="file" class="form-control assignment-form-file-input" id="files"
                                        name="files[]" multiple
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt,.jpg,.jpeg,.png,.gif">
                                    <small class="assignment-form-help">{{ __('student.upload_help') }}</small>
                                    <div id="file-preview" class="mt-3"></div>
                                </div>

                                <button type="submit" class="theme-btn py-2 px-4">
                                    <i class="fas fa-sync"></i> {{ __('student.resubmit_assignment') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <form id="assignment-form" enctype="multipart/form-data" data-assignment-id="{{ $assignment->id }}"
                        data-course-id="{{ $assignment->topic->course_id }}" class="assignment-form">
                        <div class="mb-4">
                            <label for="submission_text" class="form-label assignment-form-label">{{ __('student.submission_text_optional') }}</label>
                            <textarea class="form-control assignment-form-textarea" id="submission_text" name="submission_text" rows="10"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="files" class="form-label assignment-form-label">{{ __('student.upload_files_optional') }}</label>
                            <input type="file" class="form-control assignment-form-file-input" id="files"
                                name="files[]" multiple
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt,.jpg,.jpeg,.png,.gif">
                            <small class="assignment-form-help">{{ __('student.upload_help') }}</small>
                            <div id="file-preview" class="mt-3"></div>
                        </div>

                        <button type="submit" class="theme-btn py-2 px-4">
                            <i class="fas fa-paper-plane"></i> {{ __('student.submit_assignment') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
