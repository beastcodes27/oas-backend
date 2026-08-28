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
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyNectaResultJobTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
    }

    private function applicationWithStudent(array $studentOverrides = []): Application
    {
        $student = Student::factory()->create(array_merge([
            'exam_type' => 'psle',
            'exam_reg_number' => 'PS0101/0023/2024',
            'exam_year' => 2024,
            'exam_confirmed' => true,
        ], $studentOverrides));

        return Application::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
        ]);
    }

    private function resultHtml(): string
    {
        return '
            <table>
                <tr><td>0023</td><td>'.$this->school->name.'</td><td>II</td><td>12</td></tr>
            </table>';
    }

    public function test_job_marks_application_as_verified(): void
    {
        $application = $this->applicationWithStudent();
        $name = $application->student->full_name;

        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response('
                <table>
                    <tr><td>0023</td><td>'.$name.'</td><td>II</td><td>12</td></tr>
                </table>', 200),
        ]);

        $job = new VerifyNectaResult($application);
        $job->handle(app(NectaVerificationService::class));

        $application->refresh();

        $this->assertEquals(VerificationStatus::Verified, $application->verification_status);
        $this->assertSame($name, $application->necta_matched_name);
        $this->assertSame('II', $application->necta_division);
        $this->assertNotNull($application->necta_verified_at);
    }

    public function test_job_marks_application_as_not_found(): void
    {
        Http::fake([
            'matokeo.necta.go.tz/*' => Http::response('
                <table>
                    <tr><td>0000</td><td>Someone Else</td><td>I</td><td>4</td></tr>
                </table>', 200),
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
            'matokeo.necta.go.tz/*' => fn () => throw new ConnectionException('timeout'),
        ]);

        $application = $this->applicationWithStudent();

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
