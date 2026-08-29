<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Application Form — {{ $application->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
        }
        .header {
            border-bottom: 3px solid #FF9030;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header .brand { font-size: 20px; font-weight: bold; color: #111827; }
        .header .brand span { color: #FF9030; }
        .header .meta { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .ref-box {
            border: 1px dashed #FF9030;
            background: #fff7ed;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .ref-box b { color: #c2410c; letter-spacing: 0.06em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; width: 30%; font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; color: #374151; }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #c2410c;
            margin: 18px 0 8px;
            border-bottom: 1px solid #FF9030;
            padding-bottom: 3px;
        }
        .declaration {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            font-size: 10.5px;
            color: #374151;
            margin-top: 8px;
        }
        .sign {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .sign .line { border-top: 1px solid #111827; width: 200px; padding-top: 4px; font-size: 10px; text-align: center; }
        .footer { margin-top: 24px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">School Online <span>System</span> (OAS)</div>
        <div class="meta">{{ $application->school?->name ?? 'Secondary School' }} · {{ $application->school?->region ?? '' }}</div>
    </div>

    <div class="title">Secondary School Application Form</div>

    <div class="ref-box">
        <b>Application Reference: {{ $application->reference }}</b>
        &nbsp;&nbsp;|&nbsp;&nbsp; Entry Level: {{ $application->entry_level }}
        &nbsp;&nbsp;|&nbsp;&nbsp; Status: {{ $application->status->label() }}
    </div>

    <div class="section-title">1. Student Details</div>
    <table>
        <tr><th>Full name</th><td>{{ $application->student?->full_name }}</td></tr>
        <tr><th>Gender</th><td>{{ ucfirst($application->student?->gender ?? '') }}</td></tr>
        <tr><th>Date of birth</th><td>{{ $application->student?->birth_date?->format('d/m/Y') }}</td></tr>
        <tr><th>Nationality</th><td>{{ $application->student?->nationality }}</td></tr>
        <tr><th>Home address</th><td>{{ $application->student?->region }}, {{ $application->student?->district }}, {{ $application->student?->ward }}</td></tr>
        <tr><th>Phone</th><td>{{ $application->student?->phone }}</td></tr>
        <tr><th>Email</th><td>{{ $application->student?->email ?? '—' }}</td></tr>
        <tr><th>Disability / special needs</th><td>{{ $application->student?->disability ?: 'None' }}</td></tr>
    </table>

    <div class="section-title">2. Academic Details (NECTA)</div>
    <table>
        <tr><th>Exam type</th><td>{{ strtoupper($application->student?->exam_type ?? '') }}</td></tr>
        <tr><th>Index number</th><td>{{ $application->student?->exam_reg_number }}</td></tr>
        <tr><th>Exam year</th><td>{{ $application->student?->exam_year }}</td></tr>
        <tr><th>Previous school</th><td>{{ $application->student?->previous_school ?? '—' }}</td></tr>
        <tr><th>Verification</th><td>{{ $application->verification_status->label() }}{{ $application->necta_division ? ' · Division '.$application->necta_division : '' }}</td></tr>
    </table>

    <div class="section-title">3. Guardian Details</div>
    <table>
        <tr><th>Guardian name</th><td>{{ $application->student?->guardian?->name }}</td></tr>
        <tr><th>Relationship</th><td>{{ $application->student?->guardian?->relation }}</td></tr>
        <tr><th>Phone</th><td>{{ $application->student?->guardian?->phone }}</td></tr>
        <tr><th>Email</th><td>{{ $application->student?->guardian?->email ?? '—' }}</td></tr>
        <tr><th>Occupation</th><td>{{ $application->student?->guardian?->occupation ?? '—' }}</td></tr>
        <tr><th>Address</th><td>{{ $application->student?->guardian?->address ?? '—' }}</td></tr>
    </table>

    <div class="section-title">4. Declaration</div>
    <div class="declaration">
        I, {{ $application->student?->guardian?->name }}, declare that the information provided in this
        application is true and correct to the best of my knowledge. I understand that any false information
        may lead to the cancellation of this application.
    </div>

    <div class="sign">
        <div class="line">Guardian / Parent signature</div>
        <div class="line">Date</div>
    </div>

    <div class="footer">
        Submitted on {{ $application->submitted_at?->format('d/m/Y H:i') }} · School Online System (OAS)
    </div>
</body>
</html>
