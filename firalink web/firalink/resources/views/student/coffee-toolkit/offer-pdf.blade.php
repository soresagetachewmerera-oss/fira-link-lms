<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Coffee Export Offer Sheet - {{ $offer->reference_no }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a0524;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #6D1A95;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-title {
            font-size: 22px;
            font-weight: bold;
            color: #6D1A95;
            letter-spacing: 0.5px;
        }
        .sub-title {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            vertical-align: top;
        }
        .badge-ref {
            background-color: #6D1A95;
            color: white;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            display: inline-block;
        }
        .section-title {
            background-color: #f3e8f8;
            color: #6D1A95;
            font-weight: bold;
            font-size: 12px;
            padding: 6px 10px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-left: 4px solid #6D1A95;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            text-align: left;
        }
        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
        }
        .price-box {
            background-color: #FAF5FF;
            border: 2px solid #6D1A95;
            padding: 12px;
            text-align: center;
            border-radius: 6px;
            margin: 15px 0;
        }
        .price-val {
            font-size: 20px;
            font-weight: bold;
            color: #6D1A95;
        }
        .disclaimer {
            font-size: 9px;
            color: #64748b;
            line-height: 1.3;
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .signatures {
            width: 100%;
            margin-top: 40px;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
        }
        .sig-line {
            width: 80%;
            border-top: 1px solid #94a3b8;
            margin-top: 40px;
            font-size: 10px;
            color: #475569;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="meta-table header">
        <tr>
            <td>
                <div class="logo-title">FIRA LINK ACADEMY &amp; TRADE SUITE</div>
                <div class="sub-title">Verified Ethiopian Coffee Export Offer</div>
                <div style="margin-top: 6px; font-size: 11px; color: #475569;">
                    Exporter: <strong>{{ $offer->exporter_name }}</strong><br>
                    Contact: {{ $offer->exporter_contact ?? 'N/A' }}
                </div>
            </td>
            <td style="text-align: right;">
                <div class="badge-ref">REF: {{ $offer->reference_no }}</div>
                <div style="margin-top: 6px; font-size: 10px; color: #475569;">
                    Date Issued: <strong>{{ $offer->created_at->format('M d, Y') }}</strong><br>
                    Offer Validity: <strong style="color: #b91c1c;">{{ $offer->validity_date->format('M d, Y') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Coffee Origin & Physical Specifications -->
    <div class="section-title">1. Technical &amp; Origin Specifications</div>
    <table class="data-table">
        <tr>
            <th width="25%">Origin Region</th>
            <td width="25%"><strong>{{ $offer->origin_region }}</strong></td>
            <th width="25%">Sub-Region / Woreda</th>
            <td width="25%">{{ $offer->sub_region ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Washing Station / Mill</th>
            <td>{{ $offer->washing_station ?? 'Certified Washing Station' }}</td>
            <th>Botanical Variety</th>
            <td>{{ $offer->variety ?? 'Indigenous Heirloom' }}</td>
        </tr>
        <tr>
            <th>Processing Method</th>
            <td><strong>{{ $offer->processing_method }}</strong></td>
            <th>Official Grade</th>
            <td><strong>{{ $offer->grade }}</strong></td>
        </tr>
        <tr>
            <th>Cupping Score (SCAA)</th>
            <td style="color: #15803d; font-weight: bold;">{{ $offer->cupping_score ? $offer->cupping_score . ' SCAA Points' : 'Specialty Grade' }}</td>
            <th>Moisture Content</th>
            <td>{{ $offer->moisture_content ? $offer->moisture_content . '%' : '10.5% - 11.5%' }}</td>
        </tr>
        <tr>
            <th>Screen Sizing</th>
            <td>{{ $offer->screen_size ?? 'Screen 14+ (85% min)' }}</td>
            <th>Harvest Crop Year</th>
            <td>{{ $offer->crop_year }}</td>
        </tr>
        @if($offer->tasting_notes)
        <tr>
            <th>Sensory Cup Profile</th>
            <td colspan="3" style="font-style: italic;">{{ $offer->tasting_notes }}</td>
        </tr>
        @endif
    </table>

    <!-- Commercial & Delivery Terms -->
    <div class="section-title">2. Commercial &amp; Shipping Terms</div>
    <table class="data-table">
        <tr>
            <th width="25%">Contract Volume</th>
            <td width="25%"><strong>{{ $offer->quantity_bags }} Bags (60 kg)</strong></td>
            <th width="25%">Total Net Weight</th>
            <td width="25%"><strong>{{ number_format($netWeightKg) }} kg ({{ number_format($netWeightLbs, 1) }} lbs)</strong></td>
        </tr>
        <tr>
            <th>Packaging Standard</th>
            <td>{{ $offer->bag_type }}</td>
            <th>Container Lot</th>
            <td>{{ ceil($offer->quantity_bags / 320) }} x 20ft FCL Container</td>
        </tr>
        <tr>
            <th>Delivery Incoterm</th>
            <td><strong>{{ $offer->incoterm }}</strong></td>
            <th>Port of Loading</th>
            <td>{{ $offer->port_of_loading }}</td>
        </tr>
        <tr>
            <th>Destination Port</th>
            <td>{{ $offer->port_of_destination ?? 'As Agreed in Sales Contract' }}</td>
            <th>Payment Terms</th>
            <td><strong>{{ $offer->payment_terms }}</strong></td>
        </tr>
    </table>

    <!-- Price Quotation Box -->
    <div class="price-box">
        <div style="font-size: 11px; text-transform: uppercase; color: #6D1A95; font-weight: bold; margin-bottom: 4px;">
            FIRM COMMERCIAL OFFER PRICE ({{ $offer->incoterm }})
        </div>
        <div class="price-val">USD ${{ number_format($offer->price_usd_per_lb, 2) }} / lb</div>
        <div style="font-size: 12px; color: #475569; margin-top: 4px;">
            Total Estimated Lot Value: <strong>USD ${{ number_format($totalValueUsd, 2) }}</strong>
        </div>
    </div>

    @if($offer->additional_terms)
    <div style="font-size: 11px; margin-top: 8px;">
        <strong>Special Conditions:</strong> {{ $offer->additional_terms }}
    </div>
    @endif

    <!-- Signature and Execution Block -->
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">
                    Authorized Signatory for Exporter<br>
                    <strong>{{ $offer->exporter_name }}</strong>
                </div>
            </td>
            <td>
                <div class="sig-line" style="margin-left: auto;">
                    Accepted &amp; Confirmed by Buyer<br>
                    Company: _______________________________
                </div>
            </td>
        </tr>
    </table>

    <!-- Regulatory Disclaimer -->
    <div class="disclaimer">
        <strong>COMMERCIAL NOTICE &amp; REGULATORY DISCLAIMER:</strong> This offer sheet is issued under standard European Contract for Coffee (ECC) and Green Coffee Association (GCA) rules. All coffee export contracts from the Federal Democratic Republic of Ethiopia are governed by the Ethiopian Coffee and Tea Authority (ECTA) directive, quality inspection protocols, and National Bank of Ethiopia (NBE) foreign exchange regulations. Subject to final pre-shipment sample approval.
    </div>

</body>
</html>
