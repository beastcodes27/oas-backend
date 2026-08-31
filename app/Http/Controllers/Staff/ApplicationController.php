<?php

namespace App\Http\Controllers\Staff;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublishJoiningInstructionRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\SchoolResource;
use App\Models\Application;
use App\Models\School;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * List all applications with optional filters.
     */
    public function index(): AnonymousResourceCollection
    {
        $applications = Application::query()
            ->with(['student.guardian', 'school'])
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->when(request('entry_level'), fn ($query, $level) => $query->where('entry_level', $level))
            ->when(request('reference'), fn ($query, $reference) => $query->where('reference', 'like', '%'.$reference.'%'))
            ->latest('submitted_at')
            ->paginate((int) request('per_page', 25));

        return ApplicationResource::collection($applications);
    }

    /**
     * Update an application's status.
     *
     * Admission officers may verify applications (pending → verified → reviewing)
     * and record a draft decision (approved/declined). Only an administrator may
     * set final statuses (selected/rejected) directly; otherwise those are
     * applied in bulk when selections are published.
     */
    public function updateStatus(UpdateApplicationStatusRequest $request, Application $application): ApplicationResource
    {
        $status = ApplicationStatus::from($request->status);

        $allowed = $request->user()->is_admin
            ? ApplicationStatus::cases()
            : [ApplicationStatus::Pending, ApplicationStatus::Verified, ApplicationStatus::Reviewing, ApplicationStatus::Approved, ApplicationStatus::Declined];

        if (! in_array($status, $allowed, true)) {
            abort(403, 'Admission officers cannot set final selection statuses.');
        }

        if (! $request->user()->is_admin && $application->status->isFinal()) {
            abort(403, 'Finalised applications cannot be changed.');
        }

        $application->update([
            'status' => $status,
            'decision_notes' => $request->validated('notes') ?? $application->decision_notes,
            'decided_at' => $status->isFinal() ? now() : $application->decided_at,
        ]);

        $application->load(['student.guardian', 'school']);

        return new ApplicationResource($application);
    }

    /**
     * Publish the joining instructions shown to selected applicants.
     *
     * Accepts either an uploaded file or an external URL, plus an optional
     * name/note. Marks the instruction as published so selected applicants
     * can download it.
     */
    public function publishJoiningInstruction(PublishJoiningInstructionRequest $request): SchoolResource
    {
        $school = School::default();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('instructions', 'public');
            $url = Storage::disk('public')->url($path);
        } else {
            $url = $request->validated('url');
        }

        $school->update([
            'joining_instruction_url' => $url,
            'joining_instruction_name' => $request->validated('name'),
            'joining_instruction_note' => $request->validated('note'),
            'joining_instruction_published_at' => now(),
        ]);

        return new SchoolResource($school->fresh());
    }
}
