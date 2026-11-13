@extends('layouts.app')

@php
    $pageTitle = __('frontend.verify_certificate') ?? 'Verify Certificate';
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle,
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="section-padding fix section-bg">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Search Header & Input Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                    style="width: 70px; height: 70px; background: rgba(var(--theme-rgb, 47, 61, 92), 0.1); color: var(--theme, #2f3d5c);">
                                    <i class="fa-solid fa-certificate fa-2x"></i>
                                </div>
                                <h3 class="fw-bold mb-2">{{ __('frontend.certificate_authenticity_verifier') ?? 'Certificate Authenticity Verifier' }}</h3>
                                <p class="text-muted mb-0">
                                    {{ __('frontend.verify_certificate_desc') ?? 'Enter the Certificate Number printed on the downloaded document to verify its official authenticity and prevent counterfeits.' }}
                                </p>
                            </div>

                            <form action="{{ route('verify-certificate') }}" method="GET" class="mt-4">
                                <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-white border-end-0 ps-3">
                                        <i class="fa-solid fa-shield-halved text-muted"></i>
                                    </span>
                                    <input type="text" name="certificate_number" class="form-control border-start-0 ps-2"
                                        placeholder="e.g. CERT-000012-A1B2C3" value="{{ $code }}" required
                                        style="font-size: 16px;">
                                    <button class="theme-btn border-0 px-4" type="submit">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i>
                                        {{ __('frontend.verify') ?? 'Verify' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Verification Result Display -->
                    @if ($result)
                        @if ($result['is_valid'])
                            <!-- Authentic Certificate Result Card -->
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4 wow fadeInUp" data-wow-delay=".2s">
                                <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle mb-2"
                                        style="width: 60px; height: 60px; color: #10b981;">
                                        <i class="fa-solid fa-circle-check fa-2x"></i>
                                    </div>
                                    <h4 class="text-white fw-bold mb-1">{{ __('frontend.authentic_certificate') ?? 'Authentic & Verified Certificate' }}</h4>
                                    <p class="mb-0 text-white-50" style="font-size: 14px;">
                                        <i class="fa-solid fa-lock me-1"></i> {{ __('frontend.verified_by_system') ?? 'Issued & verified by our official portal.' }}
                                    </p>
                                </div>

                                <div class="card-body p-4 p-md-5 bg-white">
                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            <div class="p-3 rounded-3 bg-light">
                                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 11px;">
                                                    {{ __('frontend.certificate_number') ?? 'Certificate Number' }}
                                                </small>
                                                <span class="fw-bold text-dark font-monospace" style="font-size: 15px;">
                                                    {{ $result['certificate_number'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="p-3 rounded-3 bg-light">
                                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 11px;">
                                                    {{ __('frontend.completion_date') ?? 'Completion Date' }}
                                                </small>
                                                <span class="fw-bold text-dark" style="font-size: 15px;">
                                                    {{ $result['completion_date'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="p-3 rounded-3 bg-light">
                                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 11px;">
                                                    {{ __('frontend.student_name') ?? 'Issued To (Student)' }}
                                                </small>
                                                <span class="fw-bold text-dark h5 mb-0 d-block">
                                                    {{ $result['student_name'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="p-3 rounded-3 bg-light">
                                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 11px;">
                                                    {{ __('frontend.course_title') ?? 'Course Name' }}
                                                </small>
                                                <span class="fw-bold text-dark h6 mb-0 d-block">
                                                    {{ $result['course_title'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="p-3 rounded-3 bg-light">
                                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 11px;">
                                                    {{ __('frontend.instructor_name') ?? 'Course Instructor' }}
                                                </small>
                                                <span class="fw-bold text-dark" style="font-size: 15px;">
                                                    {{ $result['instructor_name'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top text-center">
                                        <small class="text-muted d-block mb-2">
                                            {{ __('frontend.verification_url') ?? 'Direct Verification URL' }}
                                        </small>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control text-center font-monospace" id="certUrlInput"
                                                value="{{ $result['verification_url'] }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('certUrlInput').value); alert('Verification link copied to clipboard!');">
                                                <i class="fa-regular fa-copy me-1"></i> Copy Link
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Unverified / Invalid Result Card -->
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4 wow fadeInUp" data-wow-delay=".2s">
                                <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle mb-2"
                                        style="width: 60px; height: 60px; color: #ef4444;">
                                        <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                                    </div>
                                    <h4 class="text-white fw-bold mb-1">{{ __('frontend.unverified_certificate') ?? 'Unverified / Invalid Certificate Code' }}</h4>
                                    <p class="mb-0 text-white-50" style="font-size: 14px;">
                                        {{ $result['message'] }}
                                    </p>
                                </div>

                                <div class="card-body p-4 text-center bg-white">
                                    <p class="text-muted mb-3">
                                        {{ __('frontend.unverified_certificate_help') ?? 'Please double-check the Certificate Number printed on the document for any typos. If you suspect fraud or have questions, please contact our support team.' }}
                                    </p>
                                    <a href="{{ route('contact') }}" class="theme-btn theme-btn-outline me-2">
                                        <i class="fa-solid fa-headset me-2"></i> {{ __('frontend.contact_support') ?? 'Contact Support' }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
