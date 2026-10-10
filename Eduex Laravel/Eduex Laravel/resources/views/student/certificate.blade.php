<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        @page {
            size: 297mm 235mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', 'Times New Roman', serif;
            background: #f4f6fb;
            width: 297mm;
            height: 210mm;
            margin: 0;
            padding: 0;
        }

        .certificate-page {
            width: 260mm;
            height: 210mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .certificate-wrapper {
            position: relative;
            width: 255mm;
            height: 180mm;
            padding: 18mm 20mm;
            background: #ffffff;
            overflow: hidden;
        }

        .certificate-background {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .certificate-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.85);
            z-index: 2;
        }

        .certificate-content {
            position: relative;
            z-index: 3;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            color: #1a1a1a;
        }

        .certificate-heading {
            margin-top: 15mm;
        }

        .certificate-heading h1 {
            font-size: 36px;
            letter-spacing: 2px;
            font-weight: 700;
            text-transform: uppercase;
            color: #2f3d5c;
        }

        .certificate-heading p {
            margin-top: 10px;
            font-size: 18px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #5f6e8b;
        }

        .recipient-name {
            font-size: 34px;
            font-weight: 700;
            color: #2f3d5c;
            margin-top: 20px;
        }

        .certificate-body {
            margin-top: 8mm;
        }

        .certificate-body .pre-title {
            font-size: 16px;
            color: #4a4a4a;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 14px;
        }

        .course-title {
            font-size: 36px;
            font-weight: 700;
            color: #2f3d5c;
            text-transform: capitalize;
        }

        .course-subtitle {
            font-size: 16px;
            color: #7a839a;
            letter-spacing: 2px;
            margin-top: 8px;
        }

        .certificate-description {
            margin: 10mm auto 0;
            width: 75%;
            font-size: 14px;
            line-height: 1.6;
            color: #4a4a4a;
        }

        .certificate-metadata {
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 10mm 10mm;
            font-size: 14px;
            color: #2f3d5c;
            font-weight: 600;
        }

        .certificate-metadata span:last-child {
            font-size: 13px;
            font-weight: 500;
            color: #5a657d;
        }

        .certificate-footer {
            width: 100%;
            padding: 0 10mm 10mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-block {
            text-align: left;
        }

        .signature-line {
            width: 220px;
            border-top: 2px solid #2f3d5c;
            margin-bottom: 8px;
        }

        .signature-name {
            font-size: 14px;
            font-weight: 600;
            color: #2f3d5c;
        }

        .signature-role {
            font-size: 12px;
            color: #7a839a;
        }

        .certificate-number {
            font-size: 12px;
            color: #7a839a;
            letter-spacing: 1px;
        }

        .other-font {
            font-family: 'Times New Roman', 'Cambria', serif;
            font-style: italic;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .certificate-wrapper {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="certificate-page">
        <div class="certificate-wrapper">
            @if (!empty($background_base64))
                <img src="data:image/png;base64,{{ $background_base64 }}" alt="Certificate background"
                    class="certificate-background">
            @endif
            <span class="certificate-overlay"></span>

            <div class="certificate-content">
                <div class="certificate-heading">
                    <h1 class="other-font">Certificate of Completion</h1>
                    <p class="other-font">This certificate proudly acknowledges</p>
                    <div class="recipient-name">{{ $student_name }}</div>
                </div>

                <div class="certificate-body">
                    <div class="pre-title other-font"><strong>has completed the</strong></div>
                    <div class="course-title">{{ $course_title }}</div>
                    <div class="course-subtitle">(Course)</div>
                    <div class="certificate-description">
                        Completion of this program signifies dedication, perseverance, and the successful mastery of the
                        required learning objectives. We applaud your commitment to excellence and continued growth.
                    </div>
                </div>

                <div class="certificate-metadata">

                    <span>Completed on: {{ $completion_date }}</span>
                </div>

                <div class="certificate-footer">
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $instructor_name }}</div>
                        <div class="signature-role">Course Instructor</div>
                    </div>
                    <div class="certificate-number">
                        Certificate No: {{ $certificate_number }}
                        <div style="font-size: 10px; color: #5f6e8b; margin-top: 3px;">
                            Verify at: {{ route('verify-certificate', ['code' => $certificate_number]) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
