<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Event Booking Ticket</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .ticket-wrapper {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 28px;
        }

        .ticket-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .ticket-header h1 {
            font-size: 20px;
            margin: 0;
            color: #111827;
        }

        .ticket-meta {
            display: flex;
            gap: 32px;
            margin-bottom: 24px;
        }

        .meta-block h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 6px;
            color: #6b7280;
        }

        .meta-block p {
            font-size: 13px;
            margin: 0;
            color: #111827;
        }

        .section-title {
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: #64748b;
        }

        .section {
            margin-bottom: 20px;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .grid .meta-block {
            flex: 1 1 160px;
        }

        .qr-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px dashed #e2e8f0;
        }

        .qr-code svg {
            width: 140px;
            height: 140px;
        }

        .qr-info {
            font-size: 11px;
            color: #4b5563;
        }

        .reference {
            font-size: 18px;
            font-weight: 600;
            color: #2563eb;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <div class="ticket-wrapper">
        <div class="ticket-header">
            <div>
                <h1>{{ $ticket['event']['title'] }}</h1>
                <div class="reference">Reference: {{ $ticket['reference'] }}</div>
            </div>
            <div class="meta-block" style="text-align: right;">
                <h3>Issued</h3>
                <p>{{ $ticket['issued_at'] }}</p>
                <div class="qr-code" style="width: 100px; height: 100px;margin-left: auto;">
                    <img src="{{ $qrCodeDataUri }}" alt="Booking QR Code">
                </div>
            </div>
        </div>

        <div class="ticket-meta" style="margin-top: -100px;">
            <div class="meta-block">
                <h3>Attendee</h3>
                <p>{{ $ticket['attendee'] }}</p>
                @if ($ticket['email'])
                    <p style="font-size: 11px; color: #6b7280; margin-top: 4px;">{{ $ticket['email'] }}</p>
                @endif
                @if ($ticket['phone'])
                    <p style="font-size: 11px; color: #6b7280;">{{ $ticket['phone'] }}</p>
                @endif
            </div>
            <div class="meta-block">
                <h3>Event Schedule</h3>
                <p>{{ $ticket['event']['start_date'] }} @ {{ $ticket['event']['start_time'] }}</p>
                @if ($ticket['event']['end_date'] || $ticket['event']['end_time'])
                    <p style="font-size: 11px; color: #6b7280;">Ends {{ $ticket['event']['end_date'] }}
                        {{ $ticket['event']['end_time'] }}</p>
                @endif
            </div>
            <div class="meta-block">
                <h3>Seats</h3>
                <p>{{ $ticket['seats'] }}</p>
                <p style="font-size: 11px; color: #06b6d4; text-transform: uppercase; margin-top: 4px;">
                    {{ $ticket['status'] }}</p>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Venue & Contact</div>
            <div class="grid">
                <div class="meta-block">
                    <h3>Location</h3>
                    <p>{{ $ticket['event']['location'] ?? 'To be announced' }}</p>
                </div>
                <div class="meta-block">
                    <h3>Event Contact</h3>
                    @if ($ticket['event']['contact_email'])
                        <p>{{ $ticket['event']['contact_email'] }}</p>
                    @endif
                    @if ($ticket['event']['contact_phone'])
                        <p>{{ $ticket['event']['contact_phone'] }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="qr-wrapper">

            <div class="qr-info">
                <strong>Scan to verify booking</strong><br>
                Present this ticket at the venue. The QR code contains your booking reference and event details for
                quick check-in.<br>
                Verification URL: {{ $verificationUrl }}
            </div>
        </div>
    </div>
</body>

</html>
