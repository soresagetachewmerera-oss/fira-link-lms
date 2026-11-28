@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Official Event Ticket Pass',
        'base_url' => route('events.index'),
    ])
@endsection

@section('content')
<section class="event-confirmation-section py-5" style="background: linear-gradient(180deg, #F9F5FF 0%, #FFFFFF 100%);">
    <div class="container">
        <!-- Success Alert -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-4 text-center shadow-sm" role="alert" style="border-left: 5px solid #10B981;">
                <i class="fa-solid fa-circle-check me-2 fs-5"></i> <strong>Registration Confirmed!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4 rounded-4 text-center shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <!-- Action Toolbar Top -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back to Events
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="btnDownloadPng" class="theme-btn btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm" style="font-size: 13.5px;">
                            <i class="fa-solid fa-download"></i>
                            <span>Download Ticket (PNG)</span>
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-light border btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 text-dark">
                            <i class="fa-solid fa-print"></i>
                            <span>Print Pass</span>
                        </button>
                    </div>
                </div>

                <!-- Digital Event Ticket Pass (Exportable as High-Res PNG) -->
                <div class="ticket-wrapper-card position-relative shadow-lg overflow-hidden" id="firaDigitalTicket" style="background: #ffffff; border-radius: 24px; border: 2px solid #E9D5FF; max-width: 680px; margin: 0 auto; box-shadow: 0 10px 40px rgba(109,26,149,0.12) !important;">
                    
                    <!-- Pass Top Header Banner -->
                    <div class="p-4 text-white position-relative" style="background: linear-gradient(135deg, #3B0764 0%, #6D1A95 100%);">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 6px 14px; border-radius: 20px;">
                                    <i class="fa-solid fa-shield-halved me-1 text-warning"></i> FIRA LINK OFFICIAL PASS
                                </span>
                                @if($booking->isStudent())
                                    <span class="badge" style="background: #FAF5FF; color: #6D1A95; font-size: 11.5px; font-weight: 800; padding: 6px 14px; border-radius: 20px;">
                                        <i class="fa-solid fa-graduation-cap me-1"></i> STUDENT PASS
                                    </span>
                                @else
                                    <span class="badge" style="background: #EEF2F6; color: #1E293B; font-size: 11.5px; font-weight: 800; padding: 6px 14px; border-radius: 20px;">
                                        <i class="fa-solid fa-user-group me-1"></i> GENERAL ATTENDEE
                                    </span>
                                @endif
                            </div>
                            <div>
                                <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill font-monospace" style="font-size: 12px; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-circle-check me-1"></i> {{ strtoupper($booking->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h2 class="fw-bold text-white mb-1" style="font-size: 1.45rem; line-height: 1.3;">
                                {{ $booking->event->title }}
                            </h2>
                            <p class="text-white-50 mb-0 small">
                                <i class="fa-solid fa-certificate text-warning me-1"></i> Masterclass &amp; Cohort Certificate Presentation Ceremony
                            </p>
                        </div>
                    </div>

                    <!-- Pass Details Body -->
                    <div class="p-4 p-md-4" style="background: #ffffff;">
                        
                        <!-- Attendee Credential Card -->
                        <div class="p-3 mb-4 rounded-4 d-flex flex-column flex-sm-row align-items-center gap-3" style="background: #FAF5FF; border: 1.5px solid #F3E8FF;">
                            <!-- Profile Photo or Initials -->
                            <div class="position-relative flex-shrink-0">
                                @if($booking->user?->hasProfilePhoto())
                                    <img src="{{ $booking->user->profile_photo_url }}" alt="{{ $booking->full_name }}" class="rounded-circle shadow-sm" style="width: 82px; height: 82px; object-fit: cover; border: 3px solid #6D1A95;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 82px; height: 82px; background: linear-gradient(135deg, #6D1A95 0%, #3B0764 100%); font-size: 26px; border: 3px solid #E9D5FF;">
                                        {{ $booking->user?->initials ?? mb_strtoupper(mb_substr($booking->first_name, 0, 1) . mb_substr($booking->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="position-absolute bottom-0 end-0 badge rounded-circle p-1 bg-success text-white" style="border: 2px solid white;" title="Verified Attendee">
                                    <i class="fa-solid fa-check" style="font-size: 10px;"></i>
                                </span>
                            </div>

                            <!-- Attendee Meta Info -->
                            <div class="text-center text-sm-start flex-grow-1 overflow-hidden">
                                <div class="text-xs text-muted text-uppercase fw-bold letter-spacing-1 mb-1">Registered Attendee</div>
                                <h3 class="fw-bold text-dark mb-1" style="font-size: 1.3rem;">{{ $booking->full_name }}</h3>
                                
                                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mt-2">
                                    @if($booking->isStudent())
                                        <span class="badge" style="background: #6D1A95; color: #ffffff; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 12px;">
                                            <i class="fa-solid fa-graduation-cap me-1"></i> Fira Link Student
                                        </span>
                                        @if($booking->student_section)
                                            <span class="badge" style="background: #F3E8FF; color: #6D1A95; font-size: 12px; font-weight: 800; padding: 5px 12px; border: 1.5px solid #8B5CF6; border-radius: 12px;">
                                                <i class="fa-solid fa-chalkboard-user me-1"></i> Section {{ $booking->student_section }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge" style="background: #EEF2F6; color: #334155; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 12px;">
                                            <i class="fa-solid fa-user-check me-1"></i> General Attendee
                                        </span>
                                    @endif

                                    @if($booking->company)
                                        <span class="badge bg-light text-muted border" style="font-size: 11.5px;">
                                            <i class="fa-solid fa-building me-1"></i> {{ $booking->company }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Event Grid Parameters -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-3 h-100" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="text-xs text-muted text-uppercase fw-semibold mb-1">
                                        <i class="fa-regular fa-calendar text-primary me-1"></i> Date
                                    </div>
                                    <div class="fw-bold text-dark small">
                                        {{ optional($booking->event->start_date)->format('D, M d, Y') ?? 'Dec 20, 2026' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-3 h-100" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="text-xs text-muted text-uppercase fw-semibold mb-1">
                                        <i class="fa-regular fa-clock text-primary me-1"></i> Time
                                    </div>
                                    <div class="fw-bold text-dark small">
                                        {{ $booking->event->start_time ? optional($booking->event->start_time)->format('h:i A') : '09:00 AM' }} EAT
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-3 h-100" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="text-xs text-muted text-uppercase fw-semibold mb-1">
                                        <i class="fa-solid fa-chair text-primary me-1"></i> Seats
                                    </div>
                                    <div class="fw-bold text-dark small">
                                        {{ $booking->seats }} {{ Str::plural('Seat', $booking->seats) }} Reserved
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-3 h-100" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="text-xs text-muted text-uppercase fw-semibold mb-1">
                                        <i class="fa-solid fa-receipt text-primary me-1"></i> Admission
                                    </div>
                                    <div class="fw-bold text-success small">
                                        {{ $booking->total_amount > 0 ? $booking->currency . ' ' . number_format($booking->total_amount, 2) : 'Free Admission' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Venue Card -->
                        <div class="p-3 mb-4 rounded-3 d-flex align-items-center gap-3" style="background: #FAF5FF; border: 1px dashed #C084FC;">
                            <div class="rounded-circle p-2 bg-white text-danger shadow-sm flex-shrink-0" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-location-dot fs-5"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-xs text-muted text-uppercase fw-bold">In-Person Event Venue</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14.5px;">
                                    {{ $booking->event->location ?? 'Vamdas Cinema, Addis Ababa, Ethiopia' }}
                                </div>
                                <div class="text-muted small text-truncate">Africa Avenue (Bole Road, near Olympia)</div>
                            </div>
                        </div>

                        <!-- Tear-off Perforation Divider -->
                        <div class="position-relative my-4" style="margin-left: -24px; margin-right: -24px;">
                            <div style="border-top: 2px dashed #CBD5E1;"></div>
                        </div>

                        <!-- Verification Reference & QR Code Section -->
                        <div class="row align-items-center g-3 pt-2">
                            <div class="col-sm-7 text-center text-sm-start">
                                <div class="text-xs text-muted text-uppercase fw-bold">Official Ticket ID</div>
                                <div class="fw-bold fs-4 text-dark font-monospace mb-1" style="color: #6D1A95 !important;">
                                    {{ $booking->reference }}
                                </div>
                                <p class="text-muted small mb-2" style="font-size: 12px;">
                                    Present this digital pass or physical printout at the venue gate for check-in.
                                </p>
                                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill" style="background: #F1F5F9; font-size: 11px; color: #475569;">
                                    <i class="fa-solid fa-fingerprint text-primary"></i>
                                    <span>Encrypted Gate Reference Ref: {{ substr($booking->verification_token ?? $booking->reference, 0, 16) }}...</span>
                                </div>
                            </div>

                            <div class="col-sm-5 text-center">
                                <div class="d-inline-block p-2 bg-white rounded-3 shadow-sm border" style="border-color: #E2E8F0 !important;">
                                    @if(!empty($qrCode))
                                        <img src="{{ $qrCode }}" alt="Ticket QR" class="img-fluid" style="width: 140px; height: 140px; display: block;">
                                    @endif
                                </div>
                                <div class="text-muted mt-1" style="font-size: 11px;">
                                    <i class="fa-solid fa-camera me-1"></i> Scan to Verify at Entrance
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Pass Security Footer -->
                    <div class="p-3 text-center text-white" style="background: #1E1B4B; font-size: 11.5px; letter-spacing: 0.3px;">
                        <i class="fa-solid fa-lock me-1 text-warning"></i> Fira Link Entry Security &bull; Non-transferable admission credential &bull; Vamdas Cinema Entrance Gate
                    </div>
                </div>

                <!-- Bottom User Actions & Sharing Section -->
                <div class="mt-4 p-4 rounded-4 bg-white shadow-sm border text-center">
                    <h5 class="fw-bold text-dark mb-2">Save or Share Your Event Ticket</h5>
                    <p class="text-muted small mb-3">Download your official PNG ticket pass to keep in your phone photos, or share it on Telegram / WhatsApp.</p>
                    
                    <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">
                        <button type="button" id="btnDownloadPngBottom" class="theme-btn d-inline-flex align-items-center gap-2 px-4 py-3 rounded-3 shadow-sm">
                            <i class="fa-solid fa-file-image fs-5"></i>
                            <span class="fw-bold">Download Ticket as PNG Image</span>
                        </button>
                        @if(!empty($verificationUrl))
                            <a href="{{ $verificationUrl }}" target="_blank" class="btn btn-outline-purple border-2 px-4 py-3 rounded-3 fw-bold" style="border-color: #6D1A95; color: #6D1A95;">
                                <i class="fa-solid fa-qrcode me-2"></i> View Gate Verification Page
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Include html2canvas for High-Res Client-Side Ticket PNG Rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ticketElement = document.getElementById('firaDigitalTicket');
        const btnTop = document.getElementById('btnDownloadPng');
        const btnBottom = document.getElementById('btnDownloadPngBottom');

        async function triggerTicketDownload() {
            if (!ticketElement || typeof html2canvas === 'undefined') {
                window.print();
                return;
            }

            const buttons = [btnTop, btnBottom].filter(Boolean);
            buttons.forEach(b => {
                b.disabled = true;
                b.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Rendering High-Res PNG...';
            });

            try {
                const canvas = await html2canvas(ticketElement, {
                    scale: 3, // 3x Retina resolution for ultra-sharp text and QR codes
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    windowWidth: 1200,
                });

                const imageUri = canvas.toDataURL('image/png');
                const fileName = 'FiraLink-Ticket-{{ $booking->reference }}.png';

                // Download link trigger
                const link = document.createElement('a');
                link.download = fileName;
                link.href = imageUri;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (err) {
                console.error('PNG render error:', err);
                alert('Could not render image automatically. Please use the Print Pass option to save as PDF.');
            } finally {
                if (btnTop) {
                    btnTop.disabled = false;
                    btnTop.innerHTML = '<i class="fa-solid fa-download"></i> <span>Download Ticket (PNG)</span>';
                }
                if (btnBottom) {
                    btnBottom.disabled = false;
                    btnBottom.innerHTML = '<i class="fa-solid fa-file-image fs-5"></i> <span class="fw-bold">Download Ticket as PNG Image</span>';
                }
            }
        }

        if (btnTop) btnTop.addEventListener('click', triggerTicketDownload);
        if (btnBottom) btnBottom.addEventListener('click', triggerTicketDownload);
    });
</script>
@endsection

