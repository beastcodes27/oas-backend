<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\School;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    /**
     * Submit a new application for the authenticated user.
     */
    public function store(StoreApplicationRequest $request): ApplicationResource
    {
        $data = $request->validated();
        $user = $request->user();

        $school = School::query()->find($data['school_id'] ?? $this->defaultSchoolId());

        if ($school !== null && ! $school->applications_open) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Applications are currently closed. The next application window has not opened yet.',
                ], 403),
            );
        }

        $application = DB::transaction(function () use ($data, $user, $school): Application {
            $student = $user->students()->create($data['student']);

            /** @var HasOne $guardianRelation */
            $guardianRelation = $student->guardian();
            $guardianRelation->create($data['guardian']);

            return $student->applications()->create([
                'user_id' => $user->id,
                'school_id' => $school?->id ?? $this->defaultSchoolId(),
                'entry_level' => $data['entry_level'],
                'reference' => $this->generateReference(),
                'status' => ApplicationStatus::Pending,
                'submitted_at' => now(),
            ]);
        });

        $application->load(['student.guardian', 'school']);

        return new ApplicationResource($application);
    }

    /**
     * List the authenticated user's applications.
     */
    public function index(): AnonymousResourceCollection
    {
        $applications = request()
            ->user()
            ->applications()
            ->with(['student.guardian', 'school'])
            ->latest('submitted_at')
            ->get();

        return ApplicationResource::collection($applications);
    }

    /**
     * Publicly track an application by its reference number.
     */
    public function track(string $reference): JsonResponse
    {
        $application = Application::with(['student', 'school'])
            ->where('reference', Str::upper($reference))
            ->first();

        if ($application === null) {
            return response()->json([
                'message' => 'No application found with the given reference.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'reference' => $application->reference,
                'entry_level' => $application->entry_level,
                'student_name' => $application->student?->full_name,
                'school' => $application->school?->name,
                'status' => [
                    'value' => $application->status->value,
                    'label' => $application->status->label(),
                ],
                'submitted_at' => $application->submitted_at?->toISOString(),
                'decided_at' => $application->decided_at?->toISOString(),
                'timeline' => (new ApplicationResource($application))->resolve()['timeline'],
            ],
        ]);
    }

    /**
     * Generate a unique, unguessable application reference.
     */
    private function generateReference(): string
    {
        do {
            $reference = 'OAS-'.strtoupper(Str::random(6)).'-'.date('Y');
        } while (Application::where('reference', $reference)->exists());

        return $reference;
    }

    private function defaultSchoolId(): ?int
    {
        return School::query()->value('id');
    }
}
