@extends('layouts.admin')

@section('title', 'Gate Ticket Scanner - ' . $event->title)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.events.attendees', $event) }}">Attendees</a></div>
        <div class="breadcrumb-item active">Gate Scanner</div>
    </div>
@endsection

@section('main-content')
<div class="section-body">
    <!-- Header Controls & Event Picker -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h2 class="section-title mb-0" style="font-size: 1.4rem;">
                <i class="fas fa-qrcode text-primary mr-2"></i>Event Gate QR Scanner
            </h2>
            <div class="text-muted small">
                Venue: <strong>{{ $event->location ?? 'Vamdas Cinema, Addis Ababa' }}</strong> &bull; 
                Date: <strong>{{ optional($event->start_date)->format('M d, Y') ?? 'Dec 20, 2026' }}</strong>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.events.attendees', $event) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-list mr-1"></i> Attendee List
            </a>
            @if($eventsList->count() > 1)
                <div class="dropdown d-inline-block">
                    <button class="btn btn-light btn-sm dropdown-toggle rounded-pill border" type="button" id="switchEventDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Switch Event
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="switchEventDropdown">
                        @foreach($eventsList as $evt)
                            <a class="dropdown-item {{ $evt->id === $event->id ? 'active' : '' }}" href="{{ route('admin.events.scanner', $evt) }}">
                                {{ Str::limit($evt->title, 35) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Live Gate Counter Strip -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 mb-2 text-center py-2" style="background: #F8FAFC;">
                <div class="text-xs text-muted text-uppercase fw-bold">Confirmed Passes</div>
                <div class="h4 mb-0 font-weight-bold text-dark" id="statTotal">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 mb-2 text-center py-2" style="background: #ECFDF5; border: 1.5px solid #A7F3D0 !important;">
                <div class="text-xs text-success text-uppercase fw-bold">Admitted at Gate</div>
                <div class="h4 mb-0 font-weight-bold text-success" id="statCheckedIn">{{ $stats['checked_in'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 mb-2 text-center py-2" style="background: #FFFBEB;">
                <div class="text-xs text-warning text-uppercase fw-bold">Yet to Arrive</div>
                <div class="h4 mb-0 font-weight-bold text-dark" id="statRemaining">{{ $stats['remaining'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 mb-2 text-center py-2" style="background: #F3E8FF;">
                <div class="text-xs text-uppercase fw-bold" style="color: #6D1A95;">Attendance Rate</div>
                <div class="h4 mb-0 font-weight-bold" style="color: #6D1A95;" id="statRate">{{ $stats['rate'] }}%</div>
            </div>
        </div>
    </div>

    <!-- Scanner & Verification UI Area -->
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9 col-12">
            
            <!-- Camera Scanner Container -->
            <div class="card shadow border-0 rounded-4 overflow-hidden mb-3" id="scannerCard">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                    <span class="small font-weight-bold">
                        <i class="fas fa-video mr-1 text-danger"></i> Gate Camera Viewfinder
                    </span>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="autoCheckInSwitch">
                        <label class="custom-control-label small text-white" for="autoCheckInSwitch" title="Immediately check in when a valid QR code is scanned">Auto Check-In</label>
                    </div>
                </div>

                <div class="card-body p-0 position-relative bg-black" style="min-height: 320px;">
                    <!-- Video QR Reader Target -->
                    <div id="reader" style="width: 100%; border: none;"></div>

                    <!-- Visual Scanner Target Aim Box Overlay -->
                    <div id="scannerOverlay" class="position-absolute d-flex flex-column align-items-center justify-content-center" style="top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 10;">
                        <div style="width: 220px; height: 220px; border: 3px solid #10B981; border-radius: 16px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.45); position: relative;">
                            <div class="scanner-laser" style="position: absolute; left: 0; right: 0; height: 2px; background: #10B981; box-shadow: 0 0 8px #10B981; animation: scanLaser 2s infinite ease-in-out;"></div>
                        </div>
                        <div class="text-white text-xs mt-3 bg-dark px-3 py-1 rounded-pill" style="opacity: 0.85;">
                            Point camera at ticket QR code
                        </div>
                    </div>
                </div>

                <!-- Camera Switcher & Controls Footer -->
                <div class="card-footer bg-light p-2 d-flex justify-content-between align-items-center">
                    <button type="button" id="btnRestartCamera" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-sync-alt mr-1"></i> Restart Camera
                    </button>
                    <span class="text-muted text-xs" id="scannerStatusText">Camera Active</span>
                </div>
            </div>

            <!-- Manual Lookup Fallback -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-3">
                    <form id="manualLookupForm" class="d-flex gap-2">
                        <input type="text" id="manualTicketInput" class="form-control form-control-lg font-monospace" placeholder="Enter Ticket Reference (e.g. EVT-ABCD1234)" autocomplete="off">
                        <button type="submit" class="btn btn-primary px-4 fw-bold text-nowrap">
                            <i class="fas fa-search mr-1"></i> Verify
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dynamic Verification Result Card (Displays dynamically on scan/lookup) -->
            <div id="resultContainer" style="display: none;">
                <!-- Result card dynamically rendered by JavaScript -->
            </div>

        </div>
    </div>
</div>

<style>
@keyframes scanLaser {
    0% { top: 5%; }
    50% { top: 95%; }
    100% { top: 5%; }
}
.fira-scanner-result-card {
    transition: all 0.3s ease;
}
</style>

<!-- Load html5-qrcode library for robust client-side QR scanning -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventId = {{ $event->id }};
    const lookupUrl = "{{ route('admin.events.scanner.lookup', $event) }}";
    const checkInBaseUrl = "{{ url('/admin/events/' . $event->id . '/scanner/check-in') }}";

    const resultContainer = document.getElementById('resultContainer');
    const scannerOverlay = document.getElementById('scannerOverlay');
    const autoCheckInSwitch = document.getElementById('autoCheckInSwitch');
    const manualForm = document.getElementById('manualLookupForm');
    const manualInput = document.getElementById('manualTicketInput');
    const scannerStatusText = document.getElementById('scannerStatusText');
    const btnRestartCamera = document.getElementById('btnRestartCamera');

    let html5QrCode = null;
    let isProcessing = false;
    let currentScannedBooking = null;

    // Web Audio synthesizer for gate chimes
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    function playBeep(type) {
        try {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'success') {
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
                osc.frequency.setValueAtTime(1760, audioCtx.currentTime + 0.1); // A6
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(180, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.4);
            }
        } catch (e) {
            // Audio error ignored
        }
    }

    // Initialize Camera Scanner
    function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            scannerStatusText.innerText = 'Scanner library loading...';
            setTimeout(startScanner, 500);
            return;
        }

        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 220, height: 220 } };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        ).catch(err => {
            console.warn("Camera start failed, falling back to manual:", err);
            scannerStatusText.innerText = "Camera unavailable (use manual lookup)";
            scannerOverlay.style.display = "none";
        });
    }

    function onScanSuccess(decodedText) {
        if (isProcessing) return;
        isProcessing = true;
        processTicketCode(decodedText);
    }

    function onScanError(error) {
        // Continuous scan error ignored
    }

    // Process Lookup via AJAX
    async function processTicketCode(code) {
        scannerStatusText.innerText = 'Verifying code...';
        
        try {
            const resp = await fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            });

            const data = await resp.json();

            if (data.success && data.booking) {
                currentScannedBooking = data.booking;
                if (data.booking.status_state === 'valid' && autoCheckInSwitch.checked) {
                    // Auto check-in mode
                    await performCheckIn(data.booking.id);
                } else {
                    renderResultCard(data.booking, data.booking.status_state);
                }
            } else {
                playBeep('error');
                renderErrorCard(data.message || 'Ticket not found.');
            }
        } catch (err) {
            playBeep('error');
            renderErrorCard('Network or server error while verifying ticket.');
        } finally {
            scannerStatusText.innerText = 'Camera Ready';
        }
    }

    // Perform Gate Check-In
    async function performCheckIn(bookingId) {
        try {
            const resp = await fetch(checkInBaseUrl + '/' + bookingId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await resp.json();
            if (data.success && data.booking) {
                playBeep('success');
                renderResultCard(data.booking, 'checked_in_now');
                updateCounters(1);
            } else {
                playBeep('error');
                renderResultCard(data.booking || currentScannedBooking, 'already_used', data.message);
            }
        } catch (err) {
            playBeep('error');
            alert('Error updating check-in status.');
        }
    }

    // Render Result Card in DOM
    function renderResultCard(b, state, customMessage) {
        resultContainer.style.display = 'block';
        resultContainer.scrollIntoView({ behavior: 'smooth' });

        let headerBg = '#10B981';
        let stateTitle = 'VALID TICKET PASS';
        let stateSubtitle = 'Ready for Admission';
        let isError = false;

        if (state === 'checked_in_now') {
            headerBg = '#10B981';
            stateTitle = 'ENTRY CONFIRMED';
            stateSubtitle = 'Attendee has been checked in successfully!';
            playBeep('success');
        } else if (state === 'already_used') {
            headerBg = '#DC2626';
            stateTitle = 'ALREADY CHECKED IN';
            stateSubtitle = customMessage || `Ticket was already used at ${b.checked_in_at || 'earlier today'}`;
            isError = true;
            playBeep('error');
        } else if (state === 'cancelled') {
            headerBg = '#DC2626';
            stateTitle = 'TICKET CANCELLED';
            stateSubtitle = 'This admission credential was cancelled.';
            isError = true;
            playBeep('error');
        } else if (state === 'valid') {
            headerBg = '#059669';
            stateTitle = 'VALID TICKET';
            stateSubtitle = 'Confirmed Pass &bull; Ready for Gate Entry';
            playBeep('success');
        }

        const photoHtml = b.profile_photo_url ? 
            `<img src="${b.profile_photo_url}" class="rounded-circle shadow" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid white;">` :
            `<div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow" style="width: 80px; height: 80px; background: #6D1A95; font-size: 24px; border: 3px solid white;">${b.initials}</div>`;

        const categoryBadge = b.is_student ?
            `<span class="badge text-white px-3 py-1 mr-1" style="background: #6D1A95; font-size: 13px;"><i class="fas fa-graduation-cap mr-1"></i> Fira Link Student</span>` +
            (b.student_section ? `<span class="badge badge-info px-3 py-1 font-weight-bold" style="font-size: 13px;">Section ${b.student_section}</span>` : '') :
            `<span class="badge badge-secondary px-3 py-1" style="font-size: 13px;"><i class="fas fa-user mr-1"></i> General Attendee</span>`;

        let actionButtonHtml = '';
        if (state === 'valid') {
            actionButtonHtml = `
                <button type="button" class="btn btn-success btn-lg btn-block py-3 fw-bold shadow rounded-pill" onclick="window.confirmGateCheckIn(${b.id})">
                    <i class="fas fa-check-circle mr-2"></i> Confirm Entry &amp; Check In
                </button>
            `;
        } else if (state === 'checked_in_now') {
            actionButtonHtml = `
                <div class="alert alert-success mb-2 py-2 text-center font-weight-bold">
                    <i class="fas fa-check-double mr-1"></i> Admitted into Venue
                </div>
            `;
        } else if (state === 'already_used') {
            actionButtonHtml = `
                <div class="alert alert-danger mb-2 py-2 text-center font-weight-bold">
                    <i class="fas fa-ban mr-1"></i> Duplicate Entry Blocked
                </div>
            `;
        }

        resultContainer.innerHTML = `
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden fira-scanner-result-card mb-4" style="border: 2px solid ${headerBg} !important;">
                <div class="p-4 text-white text-center" style="background: ${headerBg};">
                    <h3 class="fw-bold mb-1 text-white">${stateTitle}</h3>
                    <p class="mb-0 text-white-50 small">${stateSubtitle}</p>
                </div>

                <div class="card-body p-4 bg-white">
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background: #FAF5FF; border: 1px solid #E9D5FF;">
                        <div class="mr-3">${photoHtml}</div>
                        <div class="overflow-hidden">
                            <h4 class="mb-1 text-dark font-weight-bold">${b.full_name}</h4>
                            <div>${categoryBadge}</div>
                            ${b.company ? `<div class="text-muted small mt-1"><i class="fas fa-building mr-1"></i>${b.company}</div>` : ''}
                        </div>
                    </div>

                    <div class="row g-2 mb-3 text-center">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded border">
                                <div class="text-xs text-muted">Ticket Reference</div>
                                <div class="font-weight-bold font-monospace text-dark">${b.reference}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded border">
                                <div class="text-xs text-muted">Reserved Seats</div>
                                <div class="font-weight-bold text-dark">${b.seats} Person(s)</div>
                            </div>
                        </div>
                    </div>

                    ${actionButtonHtml}

                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-outline-dark btn-block rounded-pill" onclick="window.resetScannerForNext()">
                            <i class="fas fa-arrow-right mr-1"></i> Scan Next Ticket
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderErrorCard(message) {
        resultContainer.style.display = 'block';
        resultContainer.innerHTML = `
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4" style="border: 2px solid #EF4444 !important;">
                <div class="p-4 text-white text-center bg-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
                    <h3 class="fw-bold mb-1 text-white">INVALID TICKET</h3>
                    <p class="mb-0 text-white-50">${message}</p>
                </div>
                <div class="card-body p-4 text-center bg-white">
                    <p class="text-muted">The scanned code does not match any confirmed booking for this event.</p>
                    <button type="button" class="btn btn-dark rounded-pill px-4" onclick="window.resetScannerForNext()">
                        <i class="fas fa-camera mr-1"></i> Scan Again
                    </button>
                </div>
            </div>
        `;
        resultContainer.scrollIntoView({ behavior: 'smooth' });
    }

    window.confirmGateCheckIn = function(bookingId) {
        performCheckIn(bookingId);
    };

    window.resetScannerForNext = function() {
        resultContainer.style.display = 'none';
        currentScannedBooking = null;
        isProcessing = false;
        manualInput.value = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    function updateCounters(checkedDelta) {
        const totalEl = document.getElementById('statTotal');
        const checkedEl = document.getElementById('statCheckedIn');
        const remainEl = document.getElementById('statRemaining');
        const rateEl = document.getElementById('statRate');

        let total = parseInt(totalEl.innerText) || 0;
        let checked = (parseInt(checkedEl.innerText) || 0) + checkedDelta;
        let remain = Math.max(0, total - checked);
        let rate = total > 0 ? ((checked / total) * 100).toFixed(1) : 0;

        checkedEl.innerText = checked;
        remainEl.innerText = remain;
        rateEl.innerText = rate + '%';
    }

    manualForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const code = manualInput.value.trim();
        if (code) {
            processTicketCode(code);
        }
    });

    btnRestartCamera.addEventListener('click', function() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                startScanner();
            }).catch(() => {
                startScanner();
            });
        } else {
            startScanner();
        }
    });

    // Start scanner automatically on load
    startScanner();
});
</script>
@endsection
