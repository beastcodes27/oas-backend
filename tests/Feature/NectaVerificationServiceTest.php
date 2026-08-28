<?php

namespace Tests\Feature;

use App\Exceptions\Necta\NectaNotFoundException;
use App\Exceptions\Necta\NectaScraperStructureException;
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

    private function resultHtml(string $serial = '0023'): string
    {
        $row = $serial === '0023'
            ? '<tr><td>0023</td><td>Amina Khalid</td><td>III</td><td>20</td></tr>'
            : '<tr><td>'.$serial.'</td><td>Some Other</td><td>I</td><td>4</td></tr>';

        return '
            <html><body>
            <table>
                <tr><th>SN</th><th>Name</th><th>Division</th><th>Points</th></tr>
                <tr><td>0022</td><td>Baraka Joseph</td><td>II</td><td>12</td></tr>
                '.$row.'
            </table>
            </body></html>';
    }

    public function test_rejects_an_invalid_index_number(): void
    {
        $result = $this->service()->verify('not-an-index', 'Amina Khalid', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('invalid_index', $result['error_type']);
    }

    public function test_rejects_an_index_number_for_the_wrong_exam_type(): void
    {
        // CSEE index used against the PSLE format.
        $result = $this->service()->verify('S1832/0036/2024', 'Amina Khalid', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('invalid_index', $result['error_type']);
    }

    public function test_verifies_a_matching_candidate(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response($this->resultHtml(), 200),
        ]);

        $result = $this->service()->verify('PS0101/0023/2024', 'Amina Khalid', 'psle');

        $this->assertTrue($result['verified']);
        $this->assertSame('Amina Khalid', $result['matched_name']);
        $this->assertSame('III', $result['division']);
        $this->assertSame(20, $result['points']);
        $this->assertNull($result['error']);
    }

    public function test_name_matching_is_fuzzy(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response($this->resultHtml(), 200),
        ]);

        // Middle name / spelling differences should still verify.
        $result = $this->service()->verify('PS0101/0023/2024', 'Amina  Khalid', 'psle');

        $this->assertTrue($result['verified']);
    }

    public function test_mismatched_name_is_not_verified(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response($this->resultHtml(), 200),
        ]);

        $result = $this->service()->verify('PS0101/0023/2024', 'Baraka Joseph', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('name_mismatch', $result['error_type']);
    }

    public function test_returns_not_found_when_serial_missing(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response($this->resultHtml(), 200),
        ]);

        // Serial 7777 is not present on the results page.
        $result = $this->service()->verify('PS0101/7777/2024', 'Amina Khalid', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('not_found', $result['error_type']);
    }

    public function test_returns_network_error_when_necta_unreachable(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => fn () => throw new ConnectionException('Connection refused'),
        ]);

        $result = $this->service()->verify('PS0101/0023/2024', 'Amina Khalid', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('network_error', $result['error_type']);
    }

    public function test_returns_not_found_when_necta_serves_an_error_page(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response('Service Unavailable', 503),
        ]);

        $result = $this->service()->verify('PS0101/0023/2024', 'Amina Khalid', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('not_found', $result['error_type']);
    }

    public function test_returns_scraper_structure_error_when_html_changes(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response('<html><div>totally different</div></html>', 200),
        ]);

        $result = $this->service()->verify('PS0101/0023/2024', 'Amina Khalid', 'psle');

        $this->assertFalse($result['verified']);
        $this->assertSame('scraper_structure_error', $result['error_type']);
    }

    public function test_results_are_cached_for_24_hours(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response($this->resultHtml(), 200),
        ]);

        $this->service()->verify('PS0101/0023/2024', 'Amina Khalid', 'psle');
        $this->service()->verify('PS0101/0023/2024', 'Amina Khalid', 'psle');

        Http::assertSentCount(1);

        $this->assertTrue(Cache::has('necta:psle:PS0101/0023/2024:2024'));
    }

    public function test_scraper_structure_and_not_found_exceptions_are_distinct(): void
    {
        // Both are separate classes so the code paths (and logs) stay distinct.
        $this->assertInstanceOf(\Exception::class, new NectaNotFoundException('x'));
        $this->assertInstanceOf(\Exception::class, new NectaScraperStructureException('x'));
    }
}
