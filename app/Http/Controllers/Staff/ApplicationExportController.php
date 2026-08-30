<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ExcelExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationExportController extends Controller
{
    private const HEADERS = [
        'Reference', 'Student', 'Gender', 'Date of Birth', 'Region', 'District', 'Ward',
        'Phone', 'Email', 'Entry Level', 'Exam', 'Index Number', 'Exam Year', 'Previous School',
        'NECTA Division', 'Guardian', 'Guardian Phone', 'Status', 'Verification', 'Submitted',
    ];

    /**
     * Export the filtered applications as xlsx or pdf.
     */
    public function download(Request $request, ExcelExporter $excel): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $applications = Application::query()
            ->with(['student.guardian', 'school'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('entry_level'), fn ($q, $level) => $q->where('entry_level', $level))
            ->when($request->query('reference'), fn ($q, $ref) => $q->where('reference', 'like', '%'.$ref.'%'))
            ->latest('submitted_at')
            ->get();

        $stamp = now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.applications', [
                'applications' => $applications,
                'generatedAt' => now(),
            ]);

            return $pdf->download('applications-'.$stamp.'.pdf');
        }

        $rows = $applications->map(function (Application $a): array {
            return [
                $a->reference,
                $a->student?->full_name,
                ucfirst((string) $a->student?->gender),
                $a->student?->birth_date?->format('d/m/Y'),
                $a->student?->region,
                $a->student?->district,
                $a->student?->ward,
                $a->student?->phone,
                $a->student?->email,
                $a->entry_level,
                strtoupper((string) $a->student?->exam_type),
                $a->student?->exam_reg_number,
                $a->student?->exam_year,
                $a->student?->previous_school,
                $a->necta_division,
                $a->student?->guardian?->name,
                $a->student?->guardian?->phone,
                $a->status->label(),
                $a->verification_status->label(),
                $a->submitted_at?->format('d/m/Y H:i'),
            ];
        })->all();

        return $excel->download('applications-'.$stamp.'.xlsx', self::HEADERS, $rows);
    }
}
