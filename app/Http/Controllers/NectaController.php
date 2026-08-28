<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NectaController extends Controller
{
    private const EXAM_LABELS = [
        'psle' => 'Standard Seven (PSLE)',
        'ftna' => 'Form Two (FTNA)',
        'csee' => 'Form Four (CSEE)',
    ];

    private const SUBJECTS = [
        'psle' => ['Kiswahili', 'English', 'Mathematics', 'Science', 'Social Studies', 'Civic & Moral Education'],
        'ftna' => ['Civics', 'History', 'Geography', 'Kiswahili', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry'],
        'csee' => ['Civics', 'History', 'Geography', 'Kiswahili', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry'],
    ];

    private const SCHOOLS = [
        'Umoja Primary School',
        'Mwanga Primary School',
        'Kilimanjaro Primary School',
        'St. Joseph Academy',
        'Moshi Primary School',
        'Arusha Primary School',
        'Zanzibar Primary School',
        'Upendo Primary School',
        'Korongoni Primary School',
        'Njiro Primary School',
    ];

    /**
     * Fetch a candidate's NECTA result by registration number and year.
     *
     * NOTE: No public NECTA API is available yet, so this endpoint returns a
     * deterministic mock result. Swap the internals for a real integration
     * without changing the response shape.
     */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exam_type' => ['required', Rule::in(array_keys(self::EXAM_LABELS))],
            'reg_number' => ['required', 'string', 'max:40'],
            'year' => ['required', 'integer', 'digits:4', 'between:2000,'.date('Y')],
        ]);

        $result = $this->mockResult($data['exam_type'], strtoupper($data['reg_number']), (int) $data['year']);

        return response()->json(['data' => $result]);
    }

    /**
     * Build a deterministic mock NECTA result from the registration details.
     *
     * @return array<string, mixed>
     */
    private function mockResult(string $examType, string $regNumber, int $year): array
    {
        $seed = crc32($regNumber.'|'.$year);
        $divisions = ['I', 'II', 'III', 'IV'];
        $division = $divisions[abs($seed) % 4];
        $basePoints = ['I' => 4, 'II' => 12, 'III' => 19, 'IV' => 26];
        $points = $basePoints[$division] + (abs($seed) % 4);

        $grades = ['A', 'B', 'C', 'D', 'E', 'F'];
        $subjects = array_map(function (string $subject, int $i) use ($seed, $grades) {
            return [
                'name' => $subject,
                'grade' => $grades[(abs($seed >> $i) + $i) % count($grades)],
            ];
        }, self::SUBJECTS[$examType], array_keys(self::SUBJECTS[$examType]));

        return [
            'candidate_name' => $this->candidateName($seed),
            'school_name' => self::SCHOOLS[abs($seed >> 3) % count(self::SCHOOLS)],
            'exam_type' => $examType,
            'exam_label' => self::EXAM_LABELS[$examType],
            'reg_number' => $regNumber,
            'year' => $year,
            'division' => $division,
            'points' => $points,
            'subjects' => $subjects,
        ];
    }

    private function candidateName(int $seed): string
    {
        $first = ['Amina', 'Baraka', 'Zawadi', 'Neema', 'Joseph', 'Grace', 'Emmanuel', 'Rehema', 'Daniel', 'Upendo'];
        $last = ['Mushi', 'Khalid', 'Massawe', 'Shirima', 'Mwakyusa', 'Msaki', 'Kimaro', 'Laizer', 'Moshi', 'Komba'];

        return $first[abs($seed) % count($first)].' '.$last[abs($seed / 7) % count($last)];
    }
}
