<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
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
