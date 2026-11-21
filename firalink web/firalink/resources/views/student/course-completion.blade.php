@extends('layouts.student')

@section('content')
    <!-- Course Completion Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    @if ($course->pricing_model !== 'free')
                        <!-- Certificate Section -->
                        <div class="certificate-section mb-5 wow fadeInUp mt-5">
                            <div class="mhq-courses-box-items mt-0"
                                style="background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2) 100%); border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                                <div
                                    style="background: white; border-radius: 15px; padding: 60px 40px; position: relative; overflow: hidden;">
                                    <div style="position: relative; z-index: 1; text-align: center;">
                                        <div class="mb-4">
                                            <i class="fas fa-certificate" style="font-size: 4rem; color: var(--theme);"></i>
                                        </div>
                                        <h3
                                            style="color: var(--header); font-size: 1.5rem; font-weight: 600; margin-bottom: 20px;">
                                            Certificate of Completion</h3>
                                        <p style="color: var(--text); font-size: 1.1rem; margin-bottom: 30px;">This is to certify that</p>
                                        <h2
                                            style="color: var(--theme); font-size: 2rem; font-weight: 700; margin-bottom: 30px; border-bottom: 3px solid var(--theme); display: inline-block; padding-bottom: 10px;">
                                            {{ $user->name }}</h2>
                                        <p style="color: var(--text); font-size: 1rem; margin-bottom: 20px;">has successfully completed the course</p>
                                        <h4
                                            style="color: var(--header); font-size: 1.3rem; font-weight: 600; margin-bottom: 30px;">
                                            {{ $course->title }}</h4>
                                        <p style="color: var(--text); font-size: 0.9rem;">Completed on
                                            {{ $enrollment->updated_at->format('F d, Y') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="{{ route('student.courses.certificate.download', $course->id) }}"
                                    class="theme-btn style-2 py-2 px-4" target="_blank">
                                    <i class="fas fa-download"></i> Download Certificate
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Free Course Completion Notice (No Certificate) -->
                        <div class="completion-hero-section mb-5 wow fadeInUp mt-5 text-center">
                            <div class="mhq-courses-box-items mt-0 p-5"
                                style="background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2) 100%); border-radius: 20px; color: white;">
                                <div style="background: white; border-radius: 15px; padding: 50px 30px; color: #1E1B4B;">
                                    <div class="mb-4">
                                        <i class="fas fa-circle-check text-success" style="font-size: 4rem;"></i>
                                    </div>
                                    <h2 style="font-weight: 700; margin-bottom: 16px;">Course Completed Successfully!</h2>
                                    <h4 style="font-weight: 600; color: var(--theme); margin-bottom: 24px;">{{ $course->title }}</h4>
                                    <p style="max-width: 600px; margin: 0 auto 20px; color: #4B5563; font-size: 1.05rem;">
                                        Congratulations on completing this introductory module. Free courses provide essential practical knowledge without certified credentials. Official certificates of completion are issued exclusively upon completion of accredited paid trade programs.
                                    </p>
                                    <p class="text-muted" style="font-size: 0.9rem;">Completed on {{ $enrollment->updated_at->format('F d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="action-buttons text-center mb-5 wow fadeInUp">
                        <a href="{{ route('student.my-courses') }}" class="theme-btn me-3 py-2 px-4">
                            <i class="fas fa-list"></i> Back to My Courses
                        </a>
                        <a href="{{ route('courses.show', $course->id) }}" class="theme-btn style-2 py-2 px-4">
                            <i class="fas fa-eye"></i> View Course Details
                        </a>
                    </div>

                    <!-- Course Summary -->
                    <div class="course-summary wow fadeInUp">
                        <div class="mhq-courses-box-items mt-0"
                            style="background: var(--bg); border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <h3 class="mb-4" style="color: var(--header); font-weight: 600;">Course Summary</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p style="margin: 0;"><strong style="color: var(--header);">Instructor:</strong> <span
                                            style="color: var(--text);">{{ $course->instructor->name }}</span></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p style="margin: 0;"><strong style="color: var(--header);">Completion Date:</strong>
                                        <span
                                            style="color: var(--text);">{{ $enrollment->updated_at->format('F d, Y') }}</span>
                                    </p>
                                </div>
                                @if ($course->category)
                                    <div class="col-md-6 mb-3">
                                        <p style="margin: 0;"><strong style="color: var(--header);">Category:</strong> <span
                                                style="color: var(--text);">{{ $course->category }}</span></p>
                                    </div>
                                @endif
                                @if ($course->difficulty)
                                    <div class="col-md-6 mb-3">
                                        <p style="margin: 0;"><strong style="color: var(--header);">Difficulty:</strong>
                                            <span
                                                style="color: var(--text); text-transform: capitalize;">{{ $course->difficulty }}</span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media print {

            .completion-header,
            .action-buttons,
            .certificate-section .theme-btn {
                display: none;
            }
        }
    </style>
@endsection
