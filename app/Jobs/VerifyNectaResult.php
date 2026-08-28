<?php

namespace App\Jobs;

use App\Enums\VerificationStatus;
use App\Models\Application;
use App\Services\NectaVerificationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Str;

/**
 * Verifies an applicant's identity against NECTA in the background.
 *
 * Scraping can be slow or fail, so this runs off the request path. The
 * application's verification_status is updated once the check completes.
 * Execution is rate-limited so a burst of new applications cannot hammer
 * NECTA's servers.
 */
class VerifyNectaResult implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted before it gives up.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Keep the job unique for this long (seconds) to avoid duplicate
     * verification runs for the same application.
     */
    public int $uniqueFor = 3600;

    public function __construct(public Application $application) {}

    /**
     * A unique verification job per application.
     */
    public function uniqueId(): string
    {
        return 'necta-verify:'.$this->application->id;
    }

    /**
     * Throttle the job so NECTA is not bombarded with requests.
     *
     * @return array<int, RateLimited>
     */
    public function middleware(): array
    {
        return [new RateLimited('necta')];
    }

    /**
     * Execute the job.
     */
    public function handle(NectaVerificationService $service): void
    {
        $application = $this->application->fresh() ?? $this->application;
        $student = $application->student;

        if ($student === null || empty($student->exam_reg_number) || empty($student->exam_year) || empty($student->exam_type)) {
            $this->mark($application, VerificationStatus::Failed, [
                'verification_error' => 'Missing NECTA examination details on the application.',
            ]);

            return;
        }

        $result = $service->verify(
            indexNumber: $student->exam_reg_number,
            expectedName: $student->full_name,
            examType: $student->exam_type,
        );

        if ($result['verified']) {
            $this->mark($application, VerificationStatus::Verified, [
                'necta_division' => $result['division'],
                'necta_matched_name' => $result['matched_name'],
                'necta_verified_at' => now(),
            ]);

            return;
        }

        $status = $result['error_type'] === 'not_found'
            ? VerificationStatus::NotFound
            : VerificationStatus::Failed;

        $this->mark($application, $status, [
            'verification_error' => Str::limit((string) $result['error'], 500),
        ]);
    }

    /**
     * Apply the verification outcome to the application record.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function mark(Application $application, VerificationStatus $status, array $attributes = []): void
    {
        $application->update([
            'verification_status' => $status,
            ...$attributes,
        ]);
    }
}
