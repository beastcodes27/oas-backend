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
 * NECTA publishes results on matokeo.necta.go.tz and onlinesys.necta.go.tz.
 * Each centre has a page listing candidates as rows of
 * [CNO, SEX, AGGT, DIV, DETAILED SUBJECTS]. NOTE: NECTA does not publish
 * candidate NAMES on these pages, so identity is verified by resolving the
 * index number to a real, published candidate record (plus the applicant
 * having confirmed their own results during the application flow).
 *
 * CNO formats in use by NECTA:
 *   - CSEE / FTNA / ACSEE: "P{centre}/{serial}"  (e.g. P0104/0002)
 *   - PSLE:                "PS{centre}{school}-{serial}" (e.g. PS0101001-0001)
 *
 * Our application index format is "{prefix}{centre}/{serial}/{year}"
 * (e.g. S0104/0002/2024). Matching is done on the numeric centre + serial so
 * the letter prefix difference (S vs P) is irrelevant. PSLE additionally needs
 * the 3-digit school sub-code, which the application format does not carry, so
 * PSLE lookups currently report "not found" until the school number is added.
 *
 * Results are cached for 24 hours keyed by index number + exam year so that
 * repeated lookups for the same candidate do not hammer NECTA's servers.
 *
 * If NECTA ever changes their HTML structure, a
 * {@see NectaScraperStructureException} is raised (and logged distinctly)
 * rather than reporting the student as "not found".
 */
class NectaVerificationService
{
    /**
     * How long to cache a fetched result for the same index number + year.
     */
    private const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * TETEA mirrors NECTA results at maktaba.tetea.org and keeps older years
     * (e.g. CSEE 2021) that NECTA's own site no longer serves. It is used as a
     * fallback source when the primary NECTA lookup fails.
     */
    private const TETEA_BASE = 'https://maktaba.tetea.org/exam-results';

    /**
     * TETEA centre-page naming per exam: directory "{EXAM}{year}" and the
     * letter prefixes NECTA used for that exam's centres. Centres use either
     * an S or a P prefix, so both are tried.
     *
     * @var array<string, array{dir: string, file_prefixes: array<int, string>}>
     */
    private const TETEA_EXAMS = [
        'ftna' => ['dir' => 'FTNA', 'file_prefixes' => ['S', 'P']],
        'csee' => ['dir' => 'CSEE', 'file_prefixes' => ['s', 'p']],
    ];

    /**
     * Exam metadata for building the real NECTA results URLs.
     *
     * Each exam publishes a year index page that links every centre's results
     * page. The centre file prefix varies per centre (S or P), so the actual
     * centre page URL is resolved from the index rather than guessed.
     *
     * @var array<string, array{
     *     host: string,
     *     dir: string,
     *     index_file: string,
     *     label: string,
     *     school_number_required?: bool
     * }>
     */
    private const EXAMS = [
        'psle' => [
            'host' => 'https://onlinesys.necta.go.tz/results',
            'dir' => 'psle',
            'index_file' => 'psle.htm',
            'label' => 'Standard 7 (PSLE)',
            'school_number_required' => true,
        ],
        'ftna' => [
            'host' => 'https://onlinesys.necta.go.tz/results',
            'dir' => 'ftna',
            'index_file' => 'ftna.htm',
            'label' => 'Form 2 (FTNA)',
        ],
        'csee' => [
            'host' => 'https://onlinesys.necta.go.tz/results',
            'dir' => 'csee',
            'index_file' => 'index.htm',
            'label' => 'Form 4 (CSEE)',
        ],
    ];

    /**
     * Verify a candidate's identity against NECTA records.
     *
     * @param  string  $indexNumber  e.g. S0104/0002/2024
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
            return $this->failure('invalid_index', 'The index number format is invalid for '.self::EXAMS[$examType]['label'].'.');
        }

        $cacheKey = $this->cacheKey($examType, $indexNumber, $parsed['year']);

        try {
            $result = Cache::remember(
                $cacheKey,
                self::CACHE_TTL_SECONDS,
                fn () => $this->fetchResult($examType, $parsed),
            );
        } catch (NectaNotFoundException $e) {
            return $this->failure('not_found', $e->getMessage());
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

        // NECTA does not publish candidate names, so the name comparison can
        // only run when a source actually provides a name (e.g. a future API).
        $matches = ($expectedName === null || $result['candidate_name'] === null)
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
     * Fetch a candidate's published result and throw on failure.
     *
     * Used by the applicant-facing lookup. Throws
     * {@see NectaNotFoundException} / {@see NectaNetworkException} /
     * {@see NectaScraperStructureException} / {@see \InvalidArgumentException}.
     *
     * @return array<string, mixed>
     */
    public function fetchRaw(string $indexNumber, string $examType): array
    {
        $parsed = $this->parseIndex($indexNumber, $examType);

        if ($parsed === null) {
            throw new \InvalidArgumentException('The index number format is invalid for '.self::EXAMS[$examType]['label'].'.');
        }

        return Cache::remember(
            $this->cacheKey($examType, $indexNumber, $parsed['year']),
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchResult($examType, $parsed),
        );
    }

    /**
     * Parse and validate an index number for the given exam type.
     *
     * @return array{centre: string, serial: string, year: int}|null
     */
    private function parseIndex(string $indexNumber, string $examType): ?array
    {
        if (! isset(self::EXAMS[$examType])) {
            return null;
        }

        if (! preg_match('/^[A-Z]{1,2}\d{4}\/\d{4}\/\d{4}$/i', $indexNumber)) {
            return null;
        }

        [$candidateNo, $serial, $year] = explode('/', $indexNumber);

        return [
            // The centre is always the last 4 digits (handles 1- or 2-letter
            // prefixes, e.g. "S1832" or "PS0101").
            'centre' => substr($candidateNo, -4),
            'serial' => $serial,
            'year' => (int) $year,
        ];
    }

    private function cacheKey(string $examType, string $indexNumber, int $year): string
    {
        return 'necta:'.$examType.':'.Str::upper($indexNumber).':'.$year;
    }

    /**
     * Fetch and parse a candidate's result, trying the primary NECTA source
     * and then the TETEA mirror.
     *
     * @param  string  $examType  psle|ftna|csee
     * @param  array{centre: string, serial: string, year: int}  $parsed
     * @return array<string, mixed>
     */
    private function fetchResult(string $examType, array $parsed): array
    {
        $exam = self::EXAMS[$examType];

        if (($exam['school_number_required'] ?? false) === true) {
            // The PSLE pages are keyed by a 7-digit school number
            // (centre + 3-digit sub-school) that our index format lacks.
            throw new NectaNotFoundException(
                'PSLE lookup requires the full NECTA school number (e.g. PS0101001-0001).',
            );
        }

        $errors = [];
        $structureFailed = false;
        $networkFailed = false;

        foreach ($this->resultSources($examType, $exam, $parsed) as $label => $source) {
            try {
                return $source();
            } catch (NectaNotFoundException $e) {
                $errors[] = $e->getMessage();
            } catch (NectaScraperStructureException $e) {
                Log::error('NECTA scraper structure changed — fix the scraper, do NOT treat as not-found.', [
                    'source' => $label,
                    'message' => $e->getMessage(),
                ]);
                $structureFailed = true;
            } catch (NectaNetworkException|ConnectionException $e) {
                Log::warning('NECTA lookup failed: network error', ['source' => $label, 'message' => $e->getMessage()]);
                $networkFailed = true;
            }
        }

        // Surface the most actionable failure: a scraper that no longer parses
        // NECTA's markup is a code problem, a network failure is transient, and
        // everything else simply means no published result.
        if ($structureFailed) {
            throw new NectaScraperStructureException('The NECTA results page structure has changed on all sources.');
        }

        if ($networkFailed) {
            throw new NectaNetworkException('NECTA results are unreachable on all sources.');
        }

        throw new NectaNotFoundException(implode(' ', $errors));
    }

    /**
     * Ordered list of result-fetching closures (primary first, then TETEA).
     *
     * @param  array{centre: string, serial: string, year: int}  $parsed
     * @return array<string, \Closure>
     */
    private function resultSources(string $examType, array $exam, array $parsed): array
    {
        return [
            'NECTA' => fn () => $this->fetchFromHost($examType, $parsed),
            'TETEA' => fn () => $this->fetchFromTetea($examType, $parsed),
        ];
    }

    /**
     * Fetch a candidate page from NECTA's own results host.
     *
     * The centre's actual results file is resolved from the year index first
     * because NECTA mixes S/P centre prefixes per school (e.g. S0231 vs P0231).
     *
     * @param  array{centre: string, serial: string, year: int}  $parsed
     * @return array<string, mixed>
     */
    private function fetchFromHost(string $examType, array $parsed): array
    {
        $url = $this->resolveCentreUrl($examType, $parsed);

        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; OAS-Verifier/1.0)'])
            ->get($url);

        if ($response->failed()) {
            throw new NectaNotFoundException("NECTA returned {$response->status()} for {$url}");
        }

        if ($response->body() === '') {
            throw new NectaNotFoundException('Empty response from NECTA.');
        }

        return $this->parseCandidateFromHtml($response->body(), $parsed['centre'], $parsed['serial']);
    }

    /**
     * Resolve the centre's results-page URL from the year index.
     *
     * @param  array{centre: string, serial: string, year: int}  $parsed
     */
    private function resolveCentreUrl(string $examType, array $parsed): string
    {
        $exam = self::EXAMS[$examType];
        $indexUrl = $exam['host'].'/'.$parsed['year'].'/'.$exam['dir'].'/'.$exam['index_file'];

        return Cache::remember(
            'necta-centre:'.$examType.':'.$parsed['year'].':'.$parsed['centre'],
            self::CACHE_TTL_SECONDS,
            function () use ($exam, $indexUrl, $parsed): string {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; OAS-Verifier/1.0)'])
                    ->get($indexUrl);

                if ($response->failed() || $response->body() === '') {
                    throw new NectaNotFoundException("NECTA index returned {$response->status()} for {$indexUrl}");
                }

                // Find the centre's results link, e.g. "results/S0231.htm" or
                // "results/p0101.htm". The letter prefix varies per centre.
                $pattern = '/[hH][rR][eE][fF]="([^"]*?(?:[A-Za-z]{1,3})?'.preg_quote($parsed['centre'], '/').'\.htm)"/';

                if (! preg_match($pattern, $response->body(), $match)) {
                    throw new NectaNotFoundException("Centre {$parsed['centre']} not found in NECTA index.");
                }

                $href = $match[1];

                return str_starts_with($href, 'http')
                    ? $href
                    : $exam['host'].'/'.$parsed['year'].'/'.$exam['dir'].'/'.$href;
            },
        );
    }

    /**
     * Fetch a candidate page from the TETEA mirror.
     *
     * @param  array{centre: string, serial: string, year: int}  $parsed
     * @return array<string, mixed>
     */
    private function fetchFromTetea(string $examType, array $parsed): array
    {
        $config = self::TETEA_EXAMS[$examType] ?? null;

        if ($config === null) {
            throw new NectaNotFoundException('No TETEA mirror available for this exam.');
        }

        $lastError = null;

        foreach ($config['file_prefixes'] as $prefix) {
            $url = self::TETEA_BASE.'/'.$config['dir'].$parsed['year'].'/'.$prefix.$parsed['centre'].'.htm';

            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; OAS-Verifier/1.0)'])
                    ->get($url);

                if ($response->failed()) {
                    $lastError = "TETEA returned {$response->status()} for {$url}";

                    continue;
                }

                if ($response->body() === '') {
                    $lastError = "TETEA returned an empty response for {$url}";

                    continue;
                }

                return $this->parseCandidateFromHtml($response->body(), $parsed['centre'], $parsed['serial']);
            } catch (NectaScraperStructureException|NectaNotFoundException $e) {
                // A parseable TETEA page is authoritative — if the serial is
                // absent there, do not try the other prefix.
                throw $e;
            }
        }

        throw new NectaNotFoundException($lastError ?? 'No TETEA centre page found.');
    }

    /**
     * Parse the candidate row for a given centre + serial from the results page.
     *
     * NECTA's HTML is frequently malformed: candidate rows are often emitted as
     * bare <td> cells with no <tr> wrapper, so the parse anchors on the
     * candidate-number cell itself and collects the following cells.
     *
     * @return array<string, mixed>
     */
    private function parseCandidateFromHtml(string $html, string $centre, string $serial): array
    {
        if (str_contains(strtolower($html), 'no results') || str_contains($html, 'hakuna matokeo')) {
            throw new NectaNotFoundException('Results page indicates no results available.');
        }

        if (! str_contains(strtolower($html), '<table')) {
            throw new NectaScraperStructureException('Results page contains no candidate table.');
        }

        // Locate the candidate number, e.g. "S0231/0001" or "P0104/0002".
        // NECTA emits candidate rows as bare <td> cells (no <tr>), so back up
        // to the cell that contains the number, then collect the following
        // cells which make up the row.
        $cnoPattern = '/\b[A-Za-z]{0,3}'.preg_quote($centre, '/')
            .'\/'.preg_quote($serial, '/').'\b/i';

        if (! preg_match($cnoPattern, $html, $match, PREG_OFFSET_CAPTURE)) {
            throw new NectaNotFoundException("Candidate {$centre}/{$serial} not found on results page.");
        }

        $cellStart = strripos(substr($html, 0, $match[0][1]), '<td');

        if ($cellStart === false) {
            throw new NectaScraperStructureException('Could not locate the candidate cell.');
        }

        $segment = substr($html, $cellStart, 8000);

        if (! preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $segment, $cells)) {
            throw new NectaScraperStructureException('Could not parse the candidate table row.');
        }

        $values = array_map(
            static fn (string $cell): string => trim(strip_tags(html_entity_decode($cell))),
            $cells[1],
        );
        $values = array_values(array_filter($values, static fn (string $v): bool => $v !== ''));

        if ($values === [] || $this->normalizeCno($values[0]) !== $this->normalizeCno($centre.$serial)) {
            throw new NectaScraperStructureException('Candidate row does not match the requested index.');
        }

        // Column layouts vary by exam:
        //   CSEE:  [CNO, SEX, AGGT, DIV, DETAILED SUBJECTS]
        //   FTNA:  [CNO, RegNo, SEX, AGGT, DIV, DETAILED SUBJECTS]
        // Detect the layout by whether the second column is a sex (M/F).
        $sexIndex = in_array(strtoupper($values[1] ?? ''), ['M', 'F'], true) ? 1 : 2;

        if (count($values) < $sexIndex + 4) {
            throw new NectaScraperStructureException('Candidate table row does not have the expected columns.');
        }

        $aggt = $values[$sexIndex + 1] ?? null;
        $division = $values[$sexIndex + 2] ?? null;
        $division = (is_string($division) && in_array(strtoupper($division), ['0', 'ABS'], true)) ? null : $division;
        $points = (is_string($aggt) && is_numeric($aggt)) ? (int) $aggt : null;
        $subjects = $this->parseSubjects($values[$sexIndex + 3] ?? '');

        if ($division === null && $points === null && $subjects === []) {
            throw new NectaScraperStructureException('Candidate row has no usable result data.');
        }

        return [
            'candidate_name' => null, // NECTA does not publish names.
            'school_name' => $this->parseSchoolName($html, $centre),
            'cno' => $values[0],
            'division' => $division,
            'points' => $points,
            'subjects' => $subjects,
        ];
    }

    /**
     * Extract the centre's school name from the page header.
     *
     * Headers differ between sources, e.g. "S1318 NANDEMBO SECONDARY SCHOOL
     * DIVISION PERFORMANCE SUMMARY" (TETEA) or "P0104 - KIBOBO SECONDARY
     * SCHOOL CENTRE DIVISION" (NECTA). The school name always sits right after
     * the centre number and is followed by "DIVISION", so the match is made on
     * the cleaned text and anchored on DIVISION (with an optional CENTRE).
     */
    private function parseSchoolName(string $html, string $centre): ?string
    {
        $text = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $text = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($text)));

        $pattern = '/\b[A-Z]{1,2}'.preg_quote($centre, '/').'\s*-?\s*'
            .'([A-Z][A-Z0-9&\'.\/()\s-]{2,80}?)'
            .'\s+(?:CENTRE\s+)?DIVISION\b/i';

        if (! preg_match($pattern, $text, $match)) {
            return null;
        }

        $name = trim($match[1]);
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        $name = (string) preg_replace('/\bCENTRE\s*$/i', '', $name);

        return $name === '' ? null : mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Parse "SUBJECT - 'GRADE'" pairs from the detailed subjects cell.
     *
     * @return array<int, array{name: string, grade: string}>
     */
    private function parseSubjects(string $text): array
    {
        $subjects = [];

        if (preg_match_all("/([A-Z][A-Z0-9\/\&\.\-\s]*?)\s*-\s*'([A-Z])'/i", $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $subjects[] = [
                    'name' => trim($match[1]),
                    'grade' => strtoupper($match[2]),
                ];
            }
        }

        return $subjects;
    }

    /**
     * Normalize a candidate number to its numeric core (centre + serial) so
     * letter prefixes, slashes and dashes are ignored when comparing.
     */
    private function normalizeCno(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
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

        if ($a === $b) {
            return true;
        }

        $aParts = preg_split('/\s+/', $a) ?: [];
        $bParts = preg_split('/\s+/', $b) ?: [];

        $aSurname = end($aParts) ?: '';
        $bSurname = end($bParts) ?: '';

        if ($this->similarity($aSurname, $bSurname) < 0.75) {
            return false;
        }

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
