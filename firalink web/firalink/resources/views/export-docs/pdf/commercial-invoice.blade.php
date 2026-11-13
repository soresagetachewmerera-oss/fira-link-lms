<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commercial Invoice - {{ $data['invoice_number'] ?? 'CI-DRAFT' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-title {
            text-align: right;
        }
        .header-title h1 {
            margin: 0;
            color: #6D1A95;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header-title .meta-line {
            font-size: 11px;
            color: #475569;
            margin-top: 3px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-grid td {
            vertical-align: top;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
        }
        .info-grid .label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6D1A95;
            margin-bottom: 3px;
        }
        .goods-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .goods-table th {
            background-color: #6D1A95;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            padding: 7px 8px;
            border: 1px solid #6D1A95;
            text-align: left;
        }
        .goods-table td {
            padding: 8px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }
        .total-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #6D1A95;
        }
        .amount-words-box {
            background-color: #faf5ff;
            border: 1px solid #d8b4fe;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .amount-words-box .label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6D1A95;
            margin-bottom: 3px;
        }
        .amount-words-box .words {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }
        .marks-box {
            background-color: #f8fafc;
            border: 1px dashed #94a3b8;
            padding: 8px;
            font-size: 10px;
            white-space: pre-line;
            line-height: 1.35;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .footer-table td {
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-box {
            border-top: 1px solid #0f172a;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Header & Title -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #6D1A95;">{{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}</div>
                <div style="color: #475569; font-size: 10px; margin-top: 2px;">PRODUCE OF ETHIOPIA &bull; COFFEE EXPORT ENTITY</div>
            </td>
            <td class="header-title" style="width: 45%; vertical-align: top;">
                <h1>Commercial Invoice</h1>
                <div class="meta-line"><strong>INVOICE NO:</strong> <span class="font-mono">{{ $data['invoice_number'] ?? 'BA/EXP/0010/2026' }}</span></div>
                <div class="meta-line"><strong>DATE:</strong> {{ $data['invoice_date'] ?? date('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <!-- Parties & Banking Info -->
    <table class="info-grid">
        <tr>
            <td style="width: 50%;">
                <div class="label">Shipper / Exporter</div>
                <strong>{{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}</strong><br>
                {!! nl2br(e($data['exporter_address'] ?? 'Addis Ababa, Ethiopia')) !!}<br>
                @if(!empty($data['exporter_tin']))
                    <strong>TIN:</strong> {{ $data['exporter_tin'] }} &bull; <strong>ECTA LIC:</strong> {{ $data['exporter_export_license'] ?? 'N/A' }}
                @endif
            </td>
            <td style="width: 50%;">
                <div class="label">Applicant / Buyer (Consignee)</div>
                <strong>{{ $data['buyer_name'] ?? 'COMPLETE COFFEE LIMITED' }}</strong><br>
                {!! nl2br(e($data['buyer_address'] ?? 'United Kingdom')) !!}
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Contract & Payment Terms</div>
                <strong>Contract No:</strong> {{ $data['contract_number'] ?? 'P301290/001' }} (Dated: {{ $data['contract_date'] ?? '2026-02-10' }})<br>
                <strong>Payment Modality:</strong> {{ $data['payment_terms'] ?? 'CAD (Cash Against Documents)' }}<br>
                <strong>Bank:</strong> {{ $data['bank_name'] ?? 'Addis International Bank S.C.' }}<br>
                @if(!empty($data['bank_swift']))
                    <strong>SWIFT:</strong> {{ $data['bank_swift'] }}
                @endif
            </td>
            <td>
                <div class="label">Transport & Logistics Corridor</div>
                <strong>Port of Loading:</strong> {{ $data['port_of_loading'] ?? 'Port of Djibouti' }}<br>
                <strong>Place of Delivery:</strong> {{ $data['place_of_delivery'] ?? 'Felixstowe, UK' }}<br>
                <strong>Vessel & Voyage:</strong> {{ $data['vessel_name'] ?? 'MSC NINGBO Voy: MA707R' }}<br>
                <strong>Container / Seal:</strong> {{ $data['container_number'] ?? 'MSCU6342671' }} / {{ $data['seal_number'] ?? 'EU08816051' }}<br>
                <strong>B/L Reference:</strong> {{ $data['bill_of_lading_number'] ?? 'MSCUJI086404' }}
            </td>
        </tr>
    </table>

    <!-- Goods & Financials Table -->
    <table class="goods-table">
        <thead>
            <tr>
                <th style="width: 30%;">Marks & Numbers</th>
                <th style="width: 35%;">Description of Goods</th>
                <th style="width: 15%;" class="text-center">Quantity & Weights</th>
                <th style="width: 10%;" class="text-right">Unit Price</th>
                <th style="width: 10%;" class="text-right">Total (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="vertical-align: top;">
                    <div class="marks-box">
<strong>SIDE-1:</strong>
{{ $data['side_mark_1'] ?? "BUNAROMA COFFEE EXPORTER\nPRODUCE OF ETHIOPIA\nICO NO: 010/0568/0011" }}

<strong>SIDE-2:</strong>
{{ $data['side_mark_2'] ?? "ETHIOPIA ARABICA COFFEE\nWASHED YIRGACHEFFE G2\nUNITED KINGDOM" }}
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <strong>{{ $data['commodity_description'] ?? 'ETHIOPIA ARABICA GREEN COFFEE' }}</strong>
                    <div style="font-size: 10px; color: #475569; margin-top: 6px;">
                        &bull; Crop Harvest: 2025/2026<br>
                        &bull; Packaging: 60kg Export Jute Bags with GrainPro Hermetic Liner<br>
                        &bull; Terms: {{ $data['incoterm'] ?? 'FOB Djibouti' }} Net Shipped Weight Basis
                    </div>
                </td>
                <td class="text-center" style="vertical-align: top;">
                    <div class="font-mono">{{ number_format($data['total_bags'] ?? 320) }} BAGS</div>
                    <div style="font-size: 9.5px; color: #475569; margin-top: 4px;">
                        Net: {{ number_format($data['total_net_weight_kg'] ?? 19200, 2) }} KG<br>
                        Gross: {{ number_format($data['total_gross_weight_kg'] ?? 19456, 2) }} KG<br>
                        <strong>Net Lbs:</strong> <span class="font-mono">{{ number_format($data['total_net_weight_lbs'] ?? 42328.75, 2) }}</span>
                    </div>
                </td>
                <td class="text-right font-mono" style="vertical-align: top;">
                    ${{ number_format($data['unit_price_usd_per_lb'] ?? 2.60, 4) }} / LB
                </td>
                <td class="text-right font-mono" style="vertical-align: top; font-size: 11px;">
                    ${{ number_format($data['total_value_usd'] ?? 110054.75, 2) }}
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="4" class="text-right">
                    TOTAL AMOUNT ({{ $data['incoterm'] ?? 'FOB Djibouti' }}):
                </td>
                <td class="text-right font-mono" style="color: #6D1A95; font-size: 12px;">
                    ${{ number_format($data['total_value_usd'] ?? 110054.75, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Amount in Words -->
    <div class="amount-words-box">
        <div class="label">Total Amount in Words (USD)</div>
        <div class="words">{{ $data['total_amount_in_words'] ?? 'ONE HUNDRED TEN THOUSAND FIFTY-FOUR UNITED STATES DOLLARS AND SEVENTY-FIVE CENTS' }}</div>
    </div>

    <!-- Signatures & Declarations -->
    <table class="footer-table">
        <tr>
            <td style="width: 60%;">
                <div style="font-size: 9px; color: #64748b; line-height: 1.35;">
                    <strong>EXPORTER WARRANTY & COMPLIANCE:</strong> We hereby certify that this invoice is true and correct, that the contents and prices of these packages are as stated, and that the coffee originates strictly from the Federal Democratic Republic of Ethiopia.
                </div>
            </td>
            <td style="width: 40%;">
                <div class="signature-box">
                    FOR: {{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}<br><br><br>
                    AUTORIZED SIGNATORY & OFFICIAL STAMP
                </div>
            </td>
        </tr>
    </table>

</body>
</html>

