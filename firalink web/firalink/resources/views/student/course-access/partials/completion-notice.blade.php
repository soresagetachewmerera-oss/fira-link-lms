<!-- Course Completion Notice -->
<div class="completion-notice-wrapper" style="padding: 20px; margin-bottom: 30px;">
    <div class="mhq-courses-box-items mt-0"
        style="background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2) 100%); border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="completion-icon" style="font-size: 3rem; color: white;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 style="color: white; margin: 0 0 10px 0; font-size: 1.5rem; font-weight: 700;">
                            {{ __('student.congratulations_course_completed') }}</h3>
                        <p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 1rem;">
                            {{ __('student.course_completed_desc') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                <a href="{{ route('student.courses.completion', $course->id) }}" class="theme-btn"
                    style="background: white; color: var(--theme); border: none; padding: 12px 30px; font-weight: 600;">
                    <i class="fas fa-certificate me-2"></i> {{ __('student.view_certificate') }}
                </a>
            </div>
        </div>
    </div>
</div>
