<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Jobs\VerifyNectaResult;
use App\Models\Application;
use App\Models\School;
use App\Models\Student;
use App\Services\NectaVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyNectaResultJobTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        // The array cache persists across tests within one process, which would
        // otherwise let one test's result satisfy another test's lookup.
        Cache::flush();

        $this->school = School::factory()->create();
    }

    private function applicationWithStudent(array $studentOverrides = []): Application
    {
        $student = Student::factory()->create(array_merge([
            'exam_type' => 'csee',
            'exam_reg_number' => 'S0104/0002/2024',
            'exam_year' => 2024,
            'exam_confirmed' => true,
        ], $studentOverrides));

        return Application::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
        ]);
    }

    private function centreHtml(string $serial = '0002'): string
    {
        return '
            <html>
              <body>
                <h3>CSEE 2024 EXAMINATION RESULTS P0104 - KIBOBO SECONDARY SCHOOL CENTRE DIVISION</h3>
                <table>
                  <tr><td>P0104/'.$serial.'</td><td>M</td><td>17</td><td>III</td><td>CIV-\'B\' HIST-\'C\' GEO-\'D\'</td></tr>
                </table>
              </body>
            </html>';
    }

    public function test_job_marks_application_as_verified(): void
    {
        $application = $this->applicationWithStudent();

        Http::fake([
            'onlinesys.necta.go.tz/*' => Http::response($this->centreHtml(), 200),
        ]);

        $job = new VerifyNectaResult($application);
        $job->handle(app(NectaVerificationService::class));

        $application->refresh();

        $this->assertEquals(VerificationStatus::Verified, $application->verification_status);
        $this->assertSame('III', $application->necta_division);
        $this->assertNotNull($application->necta_verified_at);
    }

    public function test_job_marks_application_as_not_found(): void
    {
        Http::fake([
            'onlinesys.necta.go.tz/*' => Http::response($this->centreHtml('9999'), 200),
            'maktaba.tetea.org/*' => Http::response('Not Found', 404),
        ]);

        $application = $this->applicationWithStudent();

        $job = new VerifyNectaResult($application);
        $job->handle(app(NectaVerificationService::class));

        $application->refresh();

        $this->assertEquals(VerificationStatus::NotFound, $application->verification_status);
        $this->assertNotNull($application->verification_error);
    }

    public function test_job_marks_application_as_failed_on_network_error(): void
    {
        Http::fake([
            'onlinesys.necta.go.tz/*' => fn () => throw new ConnectionException('timeout'),
        ]);

        // A distinct index so the 24h cache from other tests cannot satisfy
        // this lookup.
        $application = $this->applicationWithStudent(['exam_reg_number' => 'S0104/0777/2024']);

        $job = new VerifyNectaResult($application);
        $job->handle(app(NectaVerificationService::class));

        $application->refresh();

        $this->assertEquals(VerificationStatus::Failed, $application->verification_status);
        $this->assertNotNull($application->verification_error);
    }

    public function test_job_fails_when_exam_details_are_missing(): void
    {
        $application = $this->applicationWithStudent([
            'exam_reg_number' => '',
        ]);

        $job = new VerifyNectaResult($application);
        $job->handle(app(NectaVerificationService::class));

        $application->refresh();

        $this->assertEquals(VerificationStatus::Failed, $application->verification_status);
    }

    public function test_job_is_unique_per_application(): void
    {
        $application = $this->applicationWithStudent();

        $job = new VerifyNectaResult($application);

        $this->assertSame('necta-verify:'.$application->id, $job->uniqueId());
    }
}
