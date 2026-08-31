<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use App\Enums\VerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    private const ORDER = [
        ApplicationStatus::Pending->value => 0,
        ApplicationStatus::Verified->value => 1,
        ApplicationStatus::Reviewing->value => 2,
        ApplicationStatus::Approved->value => 2,
        ApplicationStatus::Declined->value => 2,
        ApplicationStatus::Selected->value => 3,
        ApplicationStatus::Rejected->value => 3,
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $verification = $this->verification_status ?? VerificationStatus::Pending;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'entry_level' => $this->entry_level,
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            'applicant_status' => [
                'value' => $this->visibleStatusForApplicant()->value,
                'label' => $this->visibleStatusForApplicant()->label(),
            ],
            'submitted_at' => $this->submitted_at?->toISOString(),
            'decided_at' => $this->decided_at?->toISOString(),
            'decision_notes' => $this->decision_notes,
            'verification_status' => [
                'value' => $verification->value,
                'label' => $verification->label(),
            ],
            'necta_division' => $this->necta_division,
            'necta_matched_name' => $this->necta_matched_name,
            'necta_verified_at' => $this->necta_verified_at?->toISOString(),
            'verification_error' => $this->verification_error,
            'student' => new StudentResource($this->whenLoaded('student')),
            'guardian' => new GuardianResource($this->student?->guardian),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'timeline' => $this->buildTimeline($status),
        ];
    }

    /**
     * Build the application progress timeline for a given status.
     *
     * @return array<int, array{title: string, text: string, state: string}>
     */
    public function timelineFor(ApplicationStatus $status): array
    {
        return $this->buildTimeline($status);
    }

    /**
     * Build the application progress timeline.
     *
     * @return array<int, array{title: string, text: string, state: string}>
     */
    private function buildTimeline(ApplicationStatus $status): array
    {
        $order = self::ORDER[$status->value];
        $submitted = $this->submitted_at?->format('d M Y \· H:i') ?? '—';

        $steps = [
            ['title' => 'Application received', 'text' => $submitted],
            ['title' => 'Documents verified', 'text' => $order >= 1 ? 'Verified' : 'Pending verification'],
            ['title' => 'Under review by the admissions board', 'text' => $order >= 2 ? 'In progress' : 'Pending'],
            ['title' => 'Selection result published', 'text' => $order >= 3 ? ($status->label().' — '.($this->decided_at?->format('d M Y') ?? 'today')) : 'Expected soon'],
        ];

        return array_map(static function (array $step, int $i) use ($order): array {
            $state = match (true) {
                $i < $order => 'done',
                $i === $order => $order >= 3 ? 'done' : 'active',
                default => 'pending',
            };

            return [
                'title' => $step['title'],
                'text' => $step['text'],
                'state' => $state,
            ];
        }, $steps, array_keys($steps));
    }
}
