<?php

namespace App\Http\Controllers;

use App\Exceptions\Necta\NectaNotFoundException;
use App\Exceptions\Necta\NectaScraperStructureException;
use App\Services\NectaVerificationService;
use Illuminate\Http\Client\ConnectionException;
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

    /**
     * Fetch a candidate's published result from NECTA using their index number.
     *
     * The index number already encodes the examination year, e.g. PS0101/0023/2024.
     */
    public function lookup(Request $request, NectaVerificationService $service): JsonResponse
    {
        $data = $request->validate([
            'exam_type' => ['required', Rule::in(array_keys(self::EXAM_LABELS))],
            'reg_number' => ['required', 'string', 'max:40'],
        ]);

        try {
            $result = $service->fetchRaw($data['reg_number'], $data['exam_type']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (NectaNotFoundException $e) {
            return response()->json([
                'message' => 'No result has been published for this index number and year yet.',
            ], 404);
        } catch (NectaScraperStructureException $e) {
            return response()->json([
                'message' => 'The NECTA results page could not be read. The school has been notified.',
            ], 502);
        } catch (ConnectionException) {
            return response()->json([
                'message' => 'NECTA results are unreachable right now. Please try again later.',
            ], 502);
        }

        return response()->json([
            'data' => [
                'candidate_name' => $result['candidate_name'],
                'school_name' => $result['school_name'],
                'cno' => $result['cno'],
                'exam_type' => $data['exam_type'],
                'exam_label' => self::EXAM_LABELS[$data['exam_type']],
                'reg_number' => $data['reg_number'],
                'division' => $result['division'],
                'points' => $result['points'],
                'subjects' => $result['subjects'],
            ],
        ]);
    }
}
