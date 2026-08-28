<?php

namespace App\Http\Controllers\Admin;

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
            ->with(['student', 'school'])
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->when(request('entry_level'), fn ($query, $level) => $query->where('entry_level', $level))
            ->when(request('reference'), fn ($query, $reference) => $query->where('reference', 'like', '%'.$reference.'%'))
            ->latest('submitted_at')
            ->paginate((int) request('per_page', 25));

        return ApplicationResource::collection($applications);
    }

    /**
     * Update an application's status.
     */
    public function updateStatus(UpdateApplicationStatusRequest $request, Application $application): ApplicationResource
    {
        $status = ApplicationStatus::from($request->status);

        $application->update([
            'status' => $status,
            'decision_notes' => $request->validated('notes') ?? $application->decision_notes,
            'decided_at' => $status->isFinal() ? now() : $application->decided_at,
        ]);

        $application->load(['student.guardian', 'school']);

        return new ApplicationResource($application);
    }
}
