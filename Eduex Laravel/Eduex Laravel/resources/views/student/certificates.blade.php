@extends('layouts.student')

@section('content')
    <!-- Certificates Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <div class="section-title wow fadeInUp">
                        <h6>{{ __('student.certificates') }}</h6>
                        <h2>{{ __('student.my_certificates') }}</h2>
                        <p>{{ __('student.download_certificates_desc') }}</p>
                    </div>

                    <div class="row g-4" style="margin-top: 30px;">
                        @forelse($completedEnrollments as $index => $enrollment)
                            <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="{{ ($index % 6) * 0.2 }}s">
                                <div class="dashboard-content-section h-100">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="me-3">
                                            <div
                                                style="width: 60px; height: 60px; background: var(--theme); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa-solid fa-certificate"
                                                    style="font-size: 30px; color: white;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h4 style="margin-bottom: 8px; color: #333;">{{ $enrollment['course_title'] }}
                                            </h4>
                                            <p class="mb-1" style="color: #666; font-size: 14px;">
                                                <i class="fa-solid fa-user me-1"></i>
                                                {{ $enrollment['instructor_name'] }}
                                            </p>
                                            <p class="mb-0" style="color: #999; font-size: 12px;">
                                                <i class="fa-solid fa-calendar me-1"></i>
                                                {{ __('student.completed_on') }} {{ optional($enrollment['completed_at'])->format('M d, Y') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        style="background: rgba(102, 126, 234, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <p class="mb-0" style="font-size: 12px; color: #666;">
                                            <strong>{{ __('student.certificate_number') }}:</strong> {{ $enrollment['certificate_number'] }}
                                        </p>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="{{ $enrollment['course_id'] ? route('student.courses.certificate.download', $enrollment['course_id']) : '#' }}"
                                            class="theme-btn flex-grow-1" style="justify-content: center;">
                                            <i class="fa-solid fa-download me-2"></i>
                                            {{ __('student.download_certificate') }}
                                        </a>
                                        <a href="{{ route('verify-certificate', ['code' => $enrollment['certificate_number']]) }}"
                                            target="_blank"
                                            class="theme-btn theme-btn-outline" style="justify-content: center; padding: 0 16px;" title="Verify Certificate Authenticity">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center wow fadeInUp" data-wow-delay=".3s"
                                    style="padding: 60px 40px;">
                                    <div class="mb-4">
                                        <i class="fa-solid fa-certificate" style="font-size: 64px; color: #6c757d;"></i>
                                    </div>
                                    <h4>{{ __('student.no_certificates_title') }}</h4>
                                    <p class="mb-4">{{ __('student.no_certificates_desc') }}</p>
                                    <a href="{{ route('student.my-courses') }}" class="theme-btn">
                                        {{ __('student.view_my_courses') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>
@endsection
