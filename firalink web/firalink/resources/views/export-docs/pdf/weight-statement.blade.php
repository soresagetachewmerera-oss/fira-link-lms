<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weight Statement - {{ $data['invoice_number'] ?? 'WS-DRAFT' }}</title>
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
            margin-bottom: 20px;
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
            margin-bottom: 20px;
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
        .weight-card {
            background-color: #faf5ff;
            border: 2px solid #6D1A95;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .weight-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .weight-grid td {
            padding: 8px 12px;
            border-bottom: 1px solid #e9d5ff;
            font-size: 12px;
        }
        .weight-grid tr:last-child td {
            border-bottom: none;
            font-size: 14px;
            font-weight: bold;
            color: #6D1A95;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
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

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #6D1A95;">{{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}</div>
                <div style="color: #475569; font-size: 10px; margin-top: 2px;">OFFICIAL EXPORT SHIPPED WEIGHT STATEMENT</div>
            </td>
            <td class="header-title" style="width: 45%; vertical-align: top;">
                <h1>Weight Statement</h1>
                <div class="meta-line"><strong>INVOICE REF:</strong> <span class="font-mono">{{ $data['invoice_number'] ?? 'BA/EXP/0010/2026' }}</span></div>
                <div class="meta-line"><strong>CONTRACT NO:</strong> {{ $data['contract_number'] ?? 'P301290/001' }}</div>
                <div class="meta-line"><strong>DATE:</strong> {{ $data['invoice_date'] ?? date('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <!-- Parties & Consignment Details -->
    <table class="info-grid">
        <tr>
            <td style="width: 50%;">
                <div class="label">Shipper / Exporter</div>
                <strong>{{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}</strong><br>
                {!! nl2br(e($data['exporter_address'] ?? 'Addis Ababa, Ethiopia')) !!}
            </td>
            <td style="width: 50%;">
                <div class="label">Consignee</div>
                <strong>{{ $data['buyer_name'] ?? 'COMPLETE COFFEE LIMITED' }}</strong><br>
                {!! nl2br(e($data['buyer_address'] ?? 'United Kingdom')) !!}
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Commodity & Description</div>
                <strong>{{ $data['commodity_description'] ?? 'ETHIOPIA ARABICA GREEN COFFEE' }}</strong><br>
                <strong>Packaging:</strong> {{ $data['packaging_type'] ?? '60kg Jute Bags with GrainPro Liner' }}<br>
                <strong>Total Bag Count:</strong> <span class="font-mono">{{ number_format($data['total_bags'] ?? 320) }} BAGS</span>
            </td>
            <td>
                <div class="label">Shipping & Weighbridge Logistics</div>
                <strong>Container No:</strong> <span class="font-mono">{{ $data['container_number'] ?? 'MSCU6342671/20DV' }}</span><br>
                <strong>Seal No:</strong> <span class="font-mono">{{ $data['seal_number'] ?? 'EU08816051' }}</span><br>
                <strong>Port of Loading:</strong> {{ $data['port_of_loading'] ?? 'Port of Djibouti' }}<br>
                <strong>Port of Discharge:</strong> {{ $data['place_of_delivery'] ?? 'Felixstowe, UK' }}
            </td>
        </tr>
    </table>

    <!-- Official Certified Weight Breakdown -->
    <div class="weight-card">
        <div style="font-weight: bold; color: #6D1A95; font-size: 12px; margin-bottom: 10px; text-transform: uppercase;">
            Certified Shipped Weight Breakdown (Basis: Shipped Net Weight)
        </div>
        <table class="weight-grid">
            <tr>
                <td>Total Number of Export Bags</td>
                <td class="text-right font-mono">{{ number_format($data['total_bags'] ?? 320) }} BAGS</td>
            </tr>
            <tr>
                <td>Certified Net Weight Per Bag</td>
                <td class="text-right font-mono">{{ number_format($data['bag_weight_kg'] ?? 60.00, 2) }} KG</td>
            </tr>
            <tr>
                <td>Tare Weight Per Bag (Jute + Liner)</td>
                <td class="text-right font-mono">{{ number_format($data['tare_weight_per_bag_kg'] ?? 0.80, 2) }} KG</td>
            </tr>
            <tr>
                <td>Total Gross Weight (Kilograms)</td>
                <td class="text-right font-mono">{{ number_format($data['total_gross_weight_kg'] ?? 19456, 2) }} KG</td>
            </tr>
            <tr>
                <td>Total Tare Weight (Kilograms)</td>
                <td class="text-right font-mono">{{ number_format(($data['total_gross_weight_kg'] ?? 19456) - ($data['total_net_weight_kg'] ?? 19200), 2) }} KG</td>
            </tr>
            <tr>
                <td>TOTAL CERTIFIED NET WEIGHT (KG)</td>
                <td class="text-right font-mono">{{ number_format($data['total_net_weight_kg'] ?? 19200, 2) }} KG</td>
            </tr>
            <tr>
                <td>TOTAL CERTIFIED NET WEIGHT (LBS)</td>
                <td class="text-right font-mono" style="font-size: 15px;">{{ number_format($data['total_net_weight_lbs'] ?? 42328.75, 2) }} LBS</td>
            </tr>
        </table>
    </div>

    <!-- Legal Certification Notice -->
    <table class="footer-table">
        <tr>
            <td style="width: 60%;">
                <div style="font-size: 9.5px; color: #64748b; line-height: 1.4;">
                    <strong>CERTIFICATION OF SHIPPED WEIGHT:</strong><br>
                    We hereby certify that the above mentioned <strong>{{ number_format($data['total_bags'] ?? 320) }} bags</strong> of green coffee have been weighed on calibrated scales under supervisor custody. The net shipped weight of <strong>{{ number_format($data['total_net_weight_kg'] ?? 19200, 2) }} KG ({{ number_format($data['total_net_weight_lbs'] ?? 42328.75, 2) }} LBS)</strong> constitutes the binding basis for commercial invoice settlement under contract terms.
                </div>
            </td>
            <td style="width: 40%;">
                <div class="signature-box">
                    FOR: {{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}<br><br><br>
                    WEIGHBRIDGE CHIEF CONTROLLER & QUALITY SUPERVISOR
                </div>
            </td>
        </tr>
    </table>

</body>
</html>

