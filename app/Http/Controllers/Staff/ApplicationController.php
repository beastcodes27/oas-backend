<?php

namespace App\Http\Controllers\Staff;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
