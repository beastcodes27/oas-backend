<?php

namespace Tests\Feature;

use App\Services\NectaVerificationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NectaVerificationServiceTest extends TestCase
{
    private function service(): NectaVerificationService
    {
        return app(NectaVerificationService::class);
    }

    /**
     * A realistic NECTA CSEE centre results page (onlinesys.necta.go.tz).
     */
    private function resultHtml(string $serial = '0002'): string
    {
        return '
            <html>
              <body>
                <h3>CSEE 2024 EXAMINATION RESULTS P0104 - KIBOBO SECONDARY SCHOOL CENTRE DIVISION</h3>
                <table>
                  <tr><td>CNO</td><td>SEX</td><td>AGGT</td><td>DIV</td><td>DETAILED SUBJECTS</td></tr>
                  <tr><td>P0104/0001</td><td>F</td><td>17</td><td>III</td><td>CIV-\'B\' HIST-\'C\'</td></tr>
                  <tr><td>P0104/'.$serial.'</td><td>M</td><td>17</td><td>III</td><td>CIV-\'B\' HIST-\'C\' GEO-\'D\'</td></tr>
                </table>
              </body>
            </html>';
    }

    /**
     * Fake the NECTA host: the year index resolves the centre's results file,
     * then the centre page is served.
     */
    private function fakeNecta(?string $indexStatus = null, ?string $centreHtml = null, string $centreFile = 'p0104.htm'): void
    {
        Http::fake([
            'onlinesys.necta.go.tz/*' => function ($request) use ($indexStatus, $centreHtml, $centreFile) {
                if (str_contains($request->url(), '/index.htm')) {
                    if ($indexStatus !== null) {
                        return Http::response('Not Found', (int) $indexStatus);
                    }

                    return Http::response('<a href="results/'.$centreFile.'">P0104</a>', 200);
                }

                if ($centreHtml === null) {
                    return Http::response($this->resultHtml(), 200);
                }

                return Http::response($centreHtml, 200);
            },
        ]);
    }

    public function test_rejects_an_invalid_index_number(): void
    {
        $result = $this->service()->verify('not-an-index', 'Amina Khalid', 'csee');

        $this->assertFalse($result['verified']);
        $this->assertSame('invalid_index', $result['error_type']);
    }

    public function test_psle_lookup_reports_not_found_without_school_number(): void
    {
        // The application's PSLE format (PS0101/0023/2024) cannot resolve the
        // 3-digit school sub-code NECTA uses, so it reports not-found clearly.
        $result = $this->service()->verify('PS0101/0023/2024', null, 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('not_found', $result['error_type']);
        $this->assertStringContainsString('school number', $result['error']);
    }

    public function test_verifies_a_csee_candidate_whose_index_resolves(): void
    {
        $this->fakeNecta();

        $result = $this->service()->verify('S0104/0002/2024', null, 'csee');

        $this->assertTrue($result['verified']);
        $this->assertSame('III', $result['division']);
        $this->assertSame(17, $result['points']);
        $this->assertSame('P0104/0002', $result['raw_result_data']['cno']);
        $this->assertSame('Kibobo Secondary School', $result['raw_result_data']['school_name']);
        $this->assertNull($result['error']);
    }

    public function test_verifies_an_s_prefixed_centre_file(): void
    {
        // Some centres are served from an S-prefixed file (e.g. s0104.htm);
        // the index resolver must honour NECTA's actual link.
        $this->fakeNecta(centreFile: 's0104.htm');

        $result = $this->service()->verify('S0104/0002/2024', null, 'csee');

        $this->assertTrue($result['verified']);
        $this->assertSame('P0104/0002', $result['raw_result_data']['cno']);
    }

    public function test_verifies_an_ftna_candidate_with_registration_number_column(): void
    {
        // FTNA rows carry an extra "PReM NO" column:
        // [CNO, RegNo, SEX, AGGT, DIV, DETAILED SUBJECTS].
        $ftnaHtml = '
            <html>
              <body>
                <h3>FTNA 2024 RESULTS S0231 - SUMVE GIRLS\' SECONDARY SCHOOL DIVISION PERFORMANCE SUMMARY</h3>
                <table>
                  <tr><td>S0231/0456</td><td>20163562653</td><td>F</td><td>15</td><td>I</td><td>CIV-\'C\' HIST-\'B\' GEO-\'B\'</td></tr>
                </table>
              </body>
            </html>';

        Http::fake([
            'onlinesys.necta.go.tz/*' => function ($request) use ($ftnaHtml) {
                if (str_contains($request->url(), '/ftna.htm')) {
                    return Http::response('<a href="results/S0231.htm">S0231</a>', 200);
                }

                return Http::response($ftnaHtml, 200);
            },
        ]);

        $result = $this->service()->verify('E0231/0456/2024', null, 'ftna');

        $this->assertTrue($result['verified']);
        $this->assertSame('I', $result['division']);
        $this->assertSame(15, $result['points']);
        $this->assertSame('S0231/0456', $result['raw_result_data']['cno']);
        $this->assertSame('Sumve Girls\' Secondary School', $result['raw_result_data']['school_name']);
    }

    public function test_name_comparison_is_skipped_when_necta_publishes_no_name(): void
    {
        $this->fakeNecta();

        $result = $this->service()->verify('S0104/0002/2024', 'Amina Khalid', 'csee');

        $this->assertTrue($result['verified']);
        $this->assertNull($result['matched_name']);
    }

    public function test_returns_not_found_when_serial_not_on_the_page(): void
    {
        $this->fakeNecta();
        Http::fake(['maktaba.tetea.org/*' => Http::response($this->resultHtml(), 200)]);

        $result = $this->service()->verify('S0104/7777/2024', null, 'csee');

        $this->assertFalse($result['verified']);
        $this->assertSame('not_found', $result['error_type']);
    }

    public function test_returns_not_found_when_necta_serves_an_error_page(): void
    {
        $this->fakeNecta(indexStatus: '404');
        Http::fake(['maktaba.tetea.org/*' => Http::response('Not Found', 404)]);

        $result = $this->service()->verify('S0104/0002/2024', null, 'csee');

        $this->assertFalse($result['verified']);
        $this->assertSame('not_found', $result['error_type']);
    }

    public function test_falls_back_to_tetea_when_necta_has_no_result(): void
    {
        // Older years (e.g. CSEE 2021) are no longer served by NECTA's own
        // site, but the TETEA mirror still publishes them.
        $this->fakeNecta(indexStatus: '404');
        Http::fake(['maktaba.tetea.org/*' => Http::response($this->resultHtml(), 200)]);

        $result = $this->service()->verify('S0104/0002/2021', null, 'csee');

        $this->assertTrue($result['verified']);
        $this->assertSame('III', $result['division']);
        $this->assertSame(17, $result['points']);
        $this->assertSame('P0104/0002', $result['raw_result_data']['cno']);
    }

    public function test_returns_network_error_when_necta_unreachable(): void
    {
        Http::fake([
            'onlinesys.necta.go.tz/*' => fn () => throw new ConnectionException('Connection refused'),
            'maktaba.tetea.org/*' => fn () => throw new ConnectionException('Connection refused'),
        ]);

        $result = $this->service()->verify('S0104/0002/2024', null, 'csee');

        $this->assertFalse($result['verified']);
        $this->assertSame('network_error', $result['error_type']);
    }

    public function test_returns_scraper_structure_error_when_html_changes(): void
    {
        Http::fake([
            'onlinesys.necta.go.tz/*' => Http::response('<html><div>totally different</div></html>', 200),
            'maktaba.tetea.org/*' => Http::response('<html><div>totally different</div></html>', 200),
        ]);

        $result = $this->service()->verify('S0104/0002/2024', null, 'csee');

        $this->assertFalse($result['verified']);
        $this->assertSame('scraper_structure_error', $result['error_type']);
    }

    public function test_results_are_cached_for_24_hours(): void
    {
        $this->fakeNecta();

        $this->service()->verify('S0104/0002/2024', null, 'csee');
        $this->service()->verify('S0104/0002/2024', null, 'csee');

        // First lookup: index + centre page. Second lookup: cached.
        Http::assertSentCount(2);

        $this->assertTrue(Cache::has('necta:csee:S0104/0002/2024:2024'));
    }

    public function test_parses_school_name_from_tetea_style_header(): void
    {
        // TETEA headers omit "CENTRE": "S1318 NANDEMBO SECONDARY SCHOOL DIVISION
        // PERFORMANCE SUMMARY" — the school name must be parsed cleanly.
        $this->fakeNecta(indexStatus: '404');
        Http::fake([
            'maktaba.tetea.org/*' => Http::response('
                <html><body>
                <h3>CSEE 2021 EXAMINATION RESULTS S1318 NANDEMBO SECONDARY SCHOOL DIVISION PERFORMANCE SUMMARY</h3>
                <table>
                  <tr><td>S1318/0099</td><td>M</td><td>15</td><td>I</td><td>CIV - \'A\' HIST - \'B\' GEO - \'B\'</td></tr>
                </table>
                </body></html>', 200),
        ]);

        $result = $this->service()->verify('S1318/0099/2021', null, 'csee');

        $this->assertTrue($result['verified']);
        $this->assertSame('I', $result['division']);
        $this->assertSame(15, $result['points']);
        $this->assertSame('Nandembo Secondary School', $result['raw_result_data']['school_name']);
        $this->assertStringNotContainsString('<', $result['raw_result_data']['school_name']);
    }

    public function test_names_match_fuzzy(): void
    {
        $service = $this->service();

        $this->assertTrue($service->namesMatch('Amina Khalid', 'Amina Khalid'));
        $this->assertTrue($service->namesMatch('Amina  Khalid', 'Amina Khalid'));
        $this->assertTrue($service->namesMatch('Amina M. Khalid', 'Amina Khalid'));
        $this->assertFalse($service->namesMatch('Baraka Joseph', 'Amina Khalid'));
        $this->assertFalse($service->namesMatch('', 'Amina Khalid'));
    }
}
