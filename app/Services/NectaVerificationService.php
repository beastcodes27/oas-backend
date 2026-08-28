<?php

namespace App\Services;

use App\Exceptions\Necta\NectaNetworkException;
use App\Exceptions\Necta\NectaNotFoundException;
use App\Exceptions\Necta\NectaScraperStructureException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Verifies a student's identity by looking up their NECTA examination result
 * using the exam index number.
 *
 * Results are cached for 24 hours keyed by index number + exam year so that
 * repeated lookups for the same candidate do not hammer NECTA's servers.
 *
 * The public NECTA results are scraped from matokeo.necta.go.tz. There is no
 * official API, so the scraper relies on the published HTML structure. If that
 * structure ever changes, a {@see NectaScraperStructureException} is raised
 * (and logged distinctly) rather than reporting the student as "not found".
 */
class NectaVerificationService
{
    /**
     * How long to cache a fetched result for the same index number + year.
     */
    private const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * NECTA results base URLs, keyed by exam type.
     *
     * @var array<string, string>
     */
    private const RESULTS_BASE_URLS = [
        'psle' => 'https://matokeo.necta.go.tz/psle',
        'ftna' => 'https://matokeo.necta.go.tz/ftna',
        'csee' => 'https://matokeo.necta.go.tz/csee',
    ];

    /**
     * Expected index number formats, keyed by exam type.
     *
     * @var array<string, array{pattern: string, label: string}>
     */
    private const INDEX_FORMATS = [
        'psle' => [
            // e.g. PS0101/0023/2024 (centre / serial / year)
            'pattern' => '/^PS\d{4}\/\d{4}\/\d{4}$/i',
            'label' => 'Standard 7 (PSLE)',
        ],
        'ftna' => [
            // e.g. E0231/0456/2022 (centre / serial / year)
            'pattern' => '/^E\d{4}\/\d{4}\/\d{4}$/i',
            'label' => 'Form 2 (FTNA)',
        ],
        'csee' => [
            // e.g. S1832/0036/2024 (centre / serial / year)
            'pattern' => '/^S\d{4}\/\d{4}\/\d{4}$/i',
            'label' => 'Form 4 (CSEE)',
        ],
    ];

    /**
     * Verify a candidate's identity against NECTA records.
     *
     * @param  string  $indexNumber  e.g. PS0101/0023/2024
     * @param  string|null  $expectedName  the name submitted by the applicant
     * @param  string  $examType  psle|ftna|csee
     * @return array{
     *     verified: bool,
     *     matched_name: ?string,
     *     division: ?string,
     *     points: ?int,
     *     raw_result_data: array,
     *     error: ?string,
     *     error_type: ?string
     * }
     */
    public function verify(string $indexNumber, ?string $expectedName, string $examType): array
    {
        $parsed = $this->parseIndex($indexNumber, $examType);

        if ($parsed === null) {
            return $this->failure('invalid_index', 'The index number format is invalid for '.self::INDEX_FORMATS[$examType]['label'].'.');
        }

        $cacheKey = $this->cacheKey($examType, $indexNumber, $parsed['year']);

        try {
            $result = Cache::remember(
                $cacheKey,
                self::CACHE_TTL_SECONDS,
                fn () => $this->fetchResult($examType, $parsed),
            );
        } catch (NectaNotFoundException) {
            return $this->failure('not_found', 'No result has been published for this index number and year yet.');
        } catch (NectaNetworkException) {
            return $this->failure('network_error', 'NECTA results are unreachable right now. Please try again later.');
        } catch (ConnectionException $e) {
            Log::warning('NECTA lookup failed: connection error', ['index' => $indexNumber, 'message' => $e->getMessage()]);

            return $this->failure('network_error', 'NECTA results are unreachable right now. Please try again later.');
        } catch (NectaScraperStructureException $e) {
            Log::error('NECTA scraper structure changed — fix the scraper, do NOT treat as not-found.', [
                'index' => $indexNumber,
                'message' => $e->getMessage(),
            ]);

            return $this->failure('scraper_structure_error', 'The NECTA results page structure has changed. The school has been notified.');
        }

        if ($result === null || $result['candidate_name'] === null) {
            return $this->failure('not_found', 'No result has been published for this index number and year yet.');
        }

        $matches = $expectedName === null
            ? null
            : $this->namesMatch($expectedName, $result['candidate_name']);

        if ($matches === false) {
            return $this->failure(
                'name_mismatch',
                'The applicant name does not match the NECTA record.',
                $result,
            );
        }

        return [
            'verified' => true,
            'matched_name' => $result['candidate_name'],
            'division' => $result['division'] ?? null,
            'points' => $result['points'] ?? null,
            'raw_result_data' => $result,
            'error' => null,
            'error_type' => null,
        ];
    }

    /**
     * Parse and validate an index number for the given exam type.
     *
     * @return array{centre: string, serial: string, year: int}|null
     */
    private function parseIndex(string $indexNumber, string $examType): ?array
    {
        $format = self::INDEX_FORMATS[$examType] ?? null;

        if ($format === null || ! preg_match($format['pattern'], $indexNumber)) {
            return null;
        }

        [$centre, $serial, $year] = explode('/', $indexNumber);

        return [
            'centre' => $centre,
            'serial' => $serial,
            'year' => (int) $year,
        ];
    }

    private function cacheKey(string $examType, string $indexNumber, int $year): string
    {
        return 'necta:'.$examType.':'.Str::upper($indexNumber).':'.$year;
    }

    /**
     * Fetch and parse a candidate's result from NECTA.
     *
     * @param  string  $examType  psle|ftna|csee
     * @param  array{centre: string, serial: string, year: int}  $parsed
     * @return array<string, mixed>
     */
    private function fetchResult(string $examType, array $parsed): array
    {
        $url = self::RESULTS_BASE_URLS[$examType].'/'.$parsed['year'].'/'.$parsed['centre'].'.htm';

        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; OAS-Verifier/1.0)'])
            ->get($url);

        if ($response->serverError()) {
            throw new NectaNotFoundException("NECTA returned {$response->status()} for {$url}");
        }

        if ($response->clientError()) {
            throw new NectaNotFoundException("NECTA returned {$response->status()} for {$url}");
        }

        if ($response->body() === '') {
            throw new NectaNotFoundException('Empty response from NECTA.');
        }

        return $this->parseCandidateFromHtml($response->body(), $parsed['serial']);
    }

    /**
     * Parse the candidate row for a given serial number from the results page.
     *
     * @param  array{centre: string, serial: string, year: int}  $parsed
     * @return array<string, mixed>
     */
    private function parseCandidateFromHtml(string $html, string $serial): array
    {
        if (str_contains(strtolower($html), 'no results') || str_contains($html, 'hakuna matokeo')) {
            throw new NectaNotFoundException('Results page indicates no results available.');
        }

        // If the page does not even contain a results table, NECTA has changed
        // their markup — this is a scraper problem, not a missing student.
        if (! str_contains(strtolower($html), '<table')) {
            throw new NectaScraperStructureException('Results page contains no candidate table.');
        }

        // Find the full <tr> that contains the candidate's serial number.
        $rowPattern = '/<tr[^>]*>(?:(?!<\/tr>).)*?<td[^>]*>\s*'
            .preg_quote($serial, '/')
            .'\s*<\/td>(?:(?!<\/tr>).)*?<\/tr>/is';

        if (! preg_match($rowPattern, $html, $row)) {
            throw new NectaNotFoundException("Serial {$serial} not found on results page.");
        }

        // Extract the cells of the matched row.
        if (! preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row[0], $cells)) {
            throw new NectaScraperStructureException('Could not parse the candidate table row.');
        }

        $values = array_map(
            static fn (string $cell): string => trim(strip_tags(html_entity_decode($cell))),
            $cells[1],
        );

        // Expected columns: [serial, name, division, points, ...subjects].
        if (count($values) < 4 || $values[1] === '') {
            throw new NectaScraperStructureException('Candidate table row does not have the expected columns.');
        }

        return [
            'candidate_name' => $values[1],
            'division' => $values[2] ?? null,
            'points' => isset($values[3]) && is_numeric($values[3]) ? (int) $values[3] : null,
            'subjects' => array_slice($values, 4),
        ];
    }

    /**
     * Fuzzy-match two names (surname + given name similarity) since applicants
     * may spell middle names differently or reorder them.
     */
    public function namesMatch(string $submitted, string $fromNecta): bool
    {
        $a = $this->normalize($submitted);
        $b = $this->normalize($fromNecta);

        if ($a === '' || $b === '') {
            return false;
        }

        // Exact match after normalisation is an instant pass.
        if ($a === $b) {
            return true;
        }

        $aParts = preg_split('/\s+/', $a) ?: [];
        $bParts = preg_split('/\s+/', $b) ?: [];

        $aSurname = end($aParts) ?: '';
        $bSurname = end($bParts) ?: '';

        $surnamesClose = $this->similarity($aSurname, $bSurname) >= 0.75;

        if (! $surnamesClose) {
            return false;
        }

        // At least one of the given names should be similar, to avoid matching
        // two unrelated candidates that happen to share a surname.
        foreach ($aParts as $aPart) {
            foreach ($bParts as $bPart) {
                if ($this->similarity($aPart, $bPart) >= 0.75) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = Str::ascii($value);
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/', '', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * Similarity ratio between two strings (0.0 – 1.0).
     */
    private function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $max = max(strlen($a), strlen($b));

        if ($max === 0) {
            return 0.0;
        }

        return (float) similar_text($a, $b) / $max;
    }

    /**
     * Build a failed verification response.
     *
     * @param  array<string, mixed>|null  $rawResult
     * @return array<string, mixed>
     */
    private function failure(string $errorType, string $message, ?array $rawResult = null): array
    {
        return [
            'verified' => false,
            'matched_name' => $rawResult['candidate_name'] ?? null,
            'division' => $rawResult['division'] ?? null,
            'points' => $rawResult['points'] ?? null,
            'raw_result_data' => $rawResult ?? [],
            'error' => $message,
            'error_type' => $errorType,
        ];
    }
}
