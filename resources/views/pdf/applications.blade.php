<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Applications Export</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111827; }
        h2 { margin: 0 0 4px; color: #111827; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; letter-spacing: 0.03em; }
    </style>
</head>
<body>
    <h2>Applications Export</h2>
    <div class="meta">Generated {{ $generatedAt->format('d/m/Y H:i') }} · {{ $applications->count() }} applications</div>

    <table>
        <thead>
            <tr>
                <th>Ref</th>
                <th>Student</th>
                <th>Entry</th>
                <th>Index</th>
                <th>Previous School</th>
                <th>NECTA</th>
                <th>Guardian</th>
                <th>Guardian Phone</th>
                <th>Status</th>
                <th>Verification</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($applications as $a)
                <tr>
                    <td>{{ $a->reference }}</td>
                    <td>{{ $a->student?->full_name }}</td>
                    <td>{{ $a->entry_level }}</td>
                    <td>{{ $a->student?->exam_reg_number }}</td>
                    <td>{{ $a->student?->previous_school }}</td>
                    <td>{{ $a->necta_division ? 'Division '.$a->necta_division : '—' }}</td>
                    <td>{{ $a->student?->guardian?->name }}</td>
                    <td>{{ $a->student?->guardian?->phone }}</td>
                    <td>{{ $a->status->label() }}</td>
                    <td>{{ $a->verification_status->label() }}</td>
                    <td>{{ $a->submitted_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
