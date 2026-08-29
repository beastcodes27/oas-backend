<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolContactRequest;
use App\Http\Requests\UpdateSchoolContentRequest;
use App\Http\Requests\UpdateWindowRequest;
use App\Http\Resources\SchoolResource;
use App\Models\Application;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    /**
     * Open or close the application window for the school.
     */
    public function updateWindow(UpdateWindowRequest $request): SchoolResource
    {
        $school = School::query()->firstOrFail();

        $school->update($request->validated());

        return new SchoolResource($school->fresh());
    }

    /**
     * Update the public school content shown to visitors.
     *
     * This includes the A-Level combinations offered and the published
     * result links (name + URL) displayed on the school page.
     */
    public function updateContent(UpdateSchoolContentRequest $request): SchoolResource
    {
        $school = School::query()->firstOrFail();

        $school->update([
            'combinations' => $request->validated('combinations') ?? [],
            'result_links' => $request->validated('result_links') ?? [],
        ]);

        return new SchoolResource($school->fresh());
    }

    /**
     * Update the public contact details (phone, email, address) shown on the
     * contact page.
     */
    public function updateContact(UpdateSchoolContactRequest $request): SchoolResource
    {
        $school = School::query()->firstOrFail();

        $school->update([
            'contact' => $request->validated('contact'),
        ]);

        return new SchoolResource($school->fresh());
    }

    /**
     * Publish the draft selection decisions to all students at once.
     *
     * Approved drafts become "selected" and declined drafts become "rejected".
     * This is the single moment students see their results.
     */
    public function publishSelections(): JsonResponse
    {
        $school = School::query()->firstOrFail();

        if ($school->selections_published_at !== null) {
            return response()->json([
                'message' => 'Selections have already been published.',
            ], 409);
        }

        $updated = DB::transaction(function () use ($school): int {
            $approved = Application::query()
                ->where('status', ApplicationStatus::Approved->value)
                ->update(['status' => ApplicationStatus::Selected, 'decided_at' => now()]);

            $declined = Application::query()
                ->where('status', ApplicationStatus::Declined->value)
                ->update(['status' => ApplicationStatus::Rejected, 'decided_at' => now()]);

            $school->update(['selections_published_at' => now()]);

            return $approved + $declined;
        });

        return response()->json([
            'message' => "Selections published to {$updated} applicants.",
            'selections_published_at' => $school->fresh()->selections_published_at?->toISOString(),
        ]);
    }
}
