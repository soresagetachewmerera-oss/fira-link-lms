<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $doc->title ?? 'Export Document' }} - {{ $data['document_number'] ?? $doc->document_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #6D1A95;
            padding-bottom: 12px;
        }
        .header-left {
            width: 55%;
            vertical-align: top;
        }
        .header-right {
            width: 45%;
            vertical-align: top;
            text-align: right;
        }
        .doc-title {
            color: #6D1A95;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 5px 0;
        }
        .exporter-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }
        .sub-header {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-line {
            font-size: 10.5px;
            color: #334155;
            margin-top: 3px;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }
        .info-card {
            border: 1px solid #cbd5e1;
            margin-bottom: 15px;
        }
        .card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 10px;
            font-weight: bold;
            color: #6D1A95;
            font-size: 10px;
            text-transform: uppercase;
        }
        .card-body {
            padding: 8px 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #6D1A95;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            padding: 7px 8px;
            border: 1px solid #6D1A95;
            text-align: left;
        }
        .data-table td {
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bg-alt { background-color: #f8fafc; }
        .declaration-box {
            background-color: #faf5ff;
            border: 1px solid #d8b4fe;
            padding: 10px;
            font-size: 10px;
            margin-top: 15px;
            margin-bottom: 25px;
            line-height: 1.4;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .signature-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 15px;
        }
        .sign-line {
            border-top: 1px solid #0f172a;
            margin-top: 45px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }
        .footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="exporter-name">{{ $data['exporter_name'] ?? ($doc->organization->name ?? 'ETHIOPIAN EXPORTER') }}</div>
                <div class="sub-header">FEDERAL DEMOCRATIC REPUBLIC OF ETHIOPIA &bull; EXPORT TRADE PARTICIPANT</div>
                @if(!empty($data['exporter_address']))
                    <div style="font-size: 10px; color: #475569; margin-top: 4px;">{{ $data['exporter_address'] }}</div>
                @endif
                @if(!empty($data['tin_number']))
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">TIN: {{ $data['tin_number'] }}</div>
                @endif
            </td>
            <td class="header-right">
                <h1 class="doc-title">{{ $doc->title }}</h1>
                <div class="meta-line"><strong>REF NO:</strong> <span class="font-mono">{{ $data['document_number'] ?? $doc->document_number }}</span></div>
                <div class="meta-line"><strong>DATE:</strong> {{ $data['document_date'] ?? date('Y-m-d') }}</div>
                @if(!empty($data['contract_number']))
                    <div class="meta-line"><strong>CONTRACT:</strong> {{ $data['contract_number'] }}</div>
                @endif
                @if(!empty($data['ico_number']))
                    <div class="meta-line"><strong>ICO NO:</strong> <span class="font-mono">{{ $data['ico_number'] }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Buyer / Consignee / Transport Details -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <div class="info-card">
                    <div class="card-header">Buyer / Consignee</div>
                    <div class="card-body">
                        <strong>{{ $data['buyer_name'] ?? ($data['consignee_name'] ?? 'TO WHOM IT MAY CONCERN') }}</strong><br>
                        {!! nl2br(e($data['buyer_address'] ?? ($data['consignee_address'] ?? ''))) !!}
                        @if(!empty($data['destination_country']))
                            <div style="margin-top: 4px;"><strong>Country:</strong> {{ $data['destination_country'] }}</div>
                        @endif
                    </div>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                <div class="info-card">
                    <div class="card-header">Shipment & Transport Details</div>
                    <div class="card-body">
                        <div><strong>Port of Loading:</strong> {{ $data['port_of_loading'] ?? 'Port of Djibouti' }}</div>
                        <div><strong>Port of Discharge:</strong> {{ $data['port_of_discharge'] ?? 'N/A' }}</div>
                        <div><strong>Final Destination:</strong> {{ $data['final_destination'] ?? 'N/A' }}</div>
                        @if(!empty($data['container_number']))
                            <div><strong>Container / Seal:</strong> <span class="font-mono">{{ $data['container_number'] }} / {{ $data['seal_number'] ?? 'N/A' }}</span></div>
                        @endif
                        @if(!empty($data['vessel_name']))
                            <div><strong>Vessel:</strong> {{ $data['vessel_name'] }}</div>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Document Particulars / Key Values -->
    @if(!empty($data['items']) && is_array($data['items']))
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Item</th>
                    <th style="width: 42%;">Description</th>
                    <th style="width: 15%;" class="text-right">Bags</th>
                    <th style="width: 17%;" class="text-right">Net Wt (KG)</th>
                    <th style="width: 18%;" class="text-right">Gross Wt (KG)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item['description'] ?? 'Ethiopia Arabica Coffee' }}</strong>
                            @if(!empty($item['marks']))
                                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">{{ $item['marks'] }}</div>
                            @endif
                        </td>
                        <td class="text-right font-mono">{{ number_format($item['bags'] ?? 0) }}</td>
                        <td class="text-right font-mono">{{ number_format($item['net_weight_kg'] ?? 0, 2) }}</td>
                        <td class="text-right font-mono">{{ number_format($item['gross_weight_kg'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Render specific trade fields -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Parameter / Field</th>
                    <th style="width: 65%;">Value / Particulars</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $skipKeys = [
                        'exporter_name', 'exporter_address', 'buyer_name', 'buyer_address',
                        'document_number', 'document_date', 'declaration_text', 'id',
                        'organization_id', 'template_id', 'status', 'pdf_path', 'xlsx_path',
                        'sha256_hash', 'created_at', 'updated_at', '_token'
                    ];
                @endphp
                @foreach($data as $key => $val)
                    @if(!is_array($val) && !in_array($key, $skipKeys) && $val !== '' && $val !== null)
                        <tr>
                            <td class="bg-alt"><strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong></td>
                            <td style="color: #0f172a; line-height: 1.5;">{!! nl2br(e($val)) !!}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Official Declaration Text -->
    <div class="declaration-box">
        <strong>DECLARATION & CERTIFICATION:</strong><br>
        {{ $data['declaration_text'] ?? 'We hereby certify that the particulars furnished above are true and correct in all respects, and that the goods described herein originate from and were produced in the Federal Democratic Republic of Ethiopia in accordance with international trade standards.' }}
    </div>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                <div style="font-size: 9.5px; color: #64748b; text-align: center;">Place & Date of Issue</div>
                <div style="font-size: 10.5px; font-weight: bold; text-align: center; margin-top: 4px;">
                    Addis Ababa, Ethiopia &bull; {{ $data['document_date'] ?? date('Y-m-d') }}
                </div>
            </td>
            <td>
                <div class="sign-line">
                    FOR AND ON BEHALF OF<br>
                    <span style="color: #6D1A95;">{{ strtoupper($data['exporter_name'] ?? ($doc->organization->name ?? 'EXPORTER')) }}</span><br>
                    <span style="font-weight: normal; font-size: 9px; color: #475569;">Authorized Export Signatory</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Fira Link Export Docs Studio &bull; Document ID: {{ $doc->document_number }} &bull; SHA-256 Verified Vault Record: {{ substr($doc->sha256_hash ?? hash('sha256', $doc->id), 0, 16) }}...
    </div>

</body>
</html>

