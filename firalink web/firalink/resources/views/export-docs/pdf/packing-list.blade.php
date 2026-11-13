<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Packing List - {{ $data['invoice_number'] ?? 'PL-DRAFT' }}</title>
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
        .manifest-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .manifest-table th {
            background-color: #6D1A95;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            padding: 7px 8px;
            border: 1px solid #6D1A95;
            text-align: left;
        }
        .manifest-table td {
            padding: 8px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; font-weight: bold; }
        .total-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #6D1A95;
        }
        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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
                <div style="color: #475569; font-size: 10px; margin-top: 2px;">SPECIALTY COFFEE LOGISTICS & PACKING MANIFEST</div>
            </td>
            <td class="header-title" style="width: 45%; vertical-align: top;">
                <h1>Packing List</h1>
                <div class="meta-line"><strong>REF INVOICE NO:</strong> <span class="font-mono">{{ $data['invoice_number'] ?? 'BA/EXP/0010/2026' }}</span></div>
                <div class="meta-line"><strong>CONTRACT NO:</strong> {{ $data['contract_number'] ?? 'P301290/001' }}</div>
                <div class="meta-line"><strong>DATE:</strong> {{ $data['invoice_date'] ?? date('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <!-- Parties & Transport Details -->
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
                <div class="label">Carrier & Shipping Corridor</div>
                <strong>Mode of Transport:</strong> Truck & Vessel (Corridor Addis &rarr; Djibouti Port)<br>
                <strong>Port of Loading:</strong> {{ $data['port_of_loading'] ?? 'Port of Djibouti' }}<br>
                <strong>Port of Discharge:</strong> {{ $data['place_of_delivery'] ?? 'Felixstowe, UK' }}<br>
                <strong>Shipping Line:</strong> {{ $data['shipping_line'] ?? 'Maersk Line' }}
            </td>
            <td>
                <div class="label">Container & Seal Verification</div>
                <strong>Container No:</strong> <span class="font-mono">{{ $data['container_number'] ?? 'MSCU6342671/20DV' }}</span><br>
                <strong>Seal No:</strong> <span class="font-mono">{{ $data['seal_number'] ?? 'EU08816051' }}</span><br>
                <strong>Vessel:</strong> {{ $data['vessel_name'] ?? 'MSC NINGBO Voy: MA707R' }}<br>
                <strong>B/L No:</strong> {{ $data['bill_of_lading_number'] ?? 'MSCUJI086404' }}
            </td>
        </tr>
    </table>

    <!-- Packaging Summary -->
    <div class="summary-card">
        <div style="font-weight: bold; color: #6D1A95; font-size: 11px; margin-bottom: 4px;">PACKAGING & STUFFING DECLARATION</div>
        <div>{{ $data['packaging_description'] ?? '320 clean new Jute Bags of 60 KGS net each, lined with hermetic GrainPro food-grade inner liners for green coffee preservation.' }}</div>
    </div>

    <!-- Goods Manifest Table -->
    <table class="manifest-table">
        <thead>
            <tr>
                <th style="width: 10%;" class="text-center">Item</th>
                <th style="width: 35%;">Marks & Bag Numbers</th>
                <th style="width: 25%;">Commodity & Origin</th>
                <th style="width: 10%;" class="text-center">Bags</th>
                <th style="width: 10%;" class="text-right">Net (KG)</th>
                <th style="width: 10%;" class="text-right">Gross (KG)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center font-mono">01</td>
                <td>
                    <strong>1 - {{ $data['total_bags'] ?? 320 }}</strong><br>
                    <div style="font-size: 9px; color: #64748b; margin-top: 3px;">
                        ICO: 010/0568/0011<br>
                        PRODUCE OF ETHIOPIA
                    </div>
                </td>
                <td>
                    <strong>{{ $data['commodity_description'] ?? 'ETHIOPIA ARABICA GREEN COFFEE' }}</strong><br>
                    <span style="font-size: 10px; color: #475569;">Harvest: 2025/2026 &bull; Grade 2 Washed</span>
                </td>
                <td class="text-center font-mono" style="font-size: 11px;">
                    {{ number_format($data['total_bags'] ?? 320) }}
                </td>
                <td class="text-right font-mono">
                    {{ number_format($data['total_net_weight_kg'] ?? 19200, 2) }}
                </td>
                <td class="text-right font-mono">
                    {{ number_format($data['total_gross_weight_kg'] ?? 19456, 2) }}
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL MANIFEST:</td>
                <td class="text-center font-mono">{{ number_format($data['total_bags'] ?? 320) }} BAGS</td>
                <td class="text-right font-mono">{{ number_format($data['total_net_weight_kg'] ?? 19200, 2) }} KG</td>
                <td class="text-right font-mono">{{ number_format($data['total_gross_weight_kg'] ?? 19456, 2) }} KG</td>
            </tr>
        </tbody>
    </table>

    <!-- Tare Calculation Table -->
    <table class="info-grid" style="margin-top: 15px;">
        <tr>
            <td style="width: 33%;">
                <div class="label">Tare Weight Per Bag</div>
                <span class="font-mono">0.80 KG</span> (Jute + Liner)
            </td>
            <td style="width: 33%;">
                <div class="label">Total Tare Weight</div>
                <span class="font-mono">{{ number_format(($data['total_gross_weight_kg'] ?? 19456) - ($data['total_net_weight_kg'] ?? 19200), 2) }} KG</span>
            </td>
            <td style="width: 34%;">
                <div class="label">Total Net Weight in LBS</div>
                <span class="font-mono">{{ number_format($data['total_net_weight_lbs'] ?? 42328.75, 2) }} LBS</span>
            </td>
        </tr>
    </table>

    <!-- Signature -->
    <table class="footer-table">
        <tr>
            <td style="width: 60%;">
                <div style="font-size: 9px; color: #64748b; line-height: 1.35;">
                    This packing manifest certifies that the goods described above have been counted, weighed, inspected, and stuffed into the designated shipping container in clean, sound, export-ready condition.
                </div>
            </td>
            <td style="width: 40%;">
                <div class="signature-box">
                    FOR: {{ $data['exporter_name'] ?? 'BUNAROMA COFFEE EXPORTER PLC' }}<br><br><br>
                    AUTHORIZED EXPORT LOGISTICS MANAGER
                </div>
            </td>
        </tr>
    </table>

</body>
</html>

