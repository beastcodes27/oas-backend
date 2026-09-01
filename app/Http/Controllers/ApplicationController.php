<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Jobs\VerifyNectaResult;
use App\Models\Application;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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

        // A user may apply once per entry level per window. A Form 1 applicant
        // from a previous intake may apply again for Form 5 in a later window,
        // but cannot submit a second application for the same level in the
        // same window.
        if ($school !== null && $school->window_opens_at !== null && $school->window_closes_at !== null) {
            $duplicate = $user->applications()
                ->where('school_id', $school->id)
                ->where('entry_level', $data['entry_level'])
                ->whereBetween('submitted_at', [$school->window_opens_at, $school->window_closes_at])
                ->exists();

            if ($duplicate) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'You already have an application for this entry level in the current window.',
                    ], 409),
                );
            }
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
                'verification_status' => VerificationStatus::Pending,
                'submitted_at' => now(),
            ]);
        });

        $application->load(['student.guardian', 'school']);

        // Verify the applicant's identity against NECTA in the background.
        VerifyNectaResult::dispatch($application);

        return new ApplicationResource($application);
    }

    /**
     * Download a printable application form (PDF) for the authenticated user's
     * application.
     */
    public function form(Application $application): Response
    {
        if ($application->user_id !== request()->user()->id) {
            abort(403, 'You can only download your own application form.');
        }

        $application->load(['student.guardian', 'school']);

        $pdf = Pdf::loadView(
            'pdf.application-form',
            ['application' => $application],
        );

        return $pdf->download('application-form-'.$application->reference.'.pdf');
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

        $visibleStatus = $application->visibleStatusForApplicant();

        return response()->json([
            'data' => [
                'reference' => $application->reference,
                'entry_level' => $application->entry_level,
                'student_name' => $application->student?->full_name,
                'school' => $application->school?->name,
                'status' => [
                    'value' => $visibleStatus->value,
                    'label' => $visibleStatus->label(),
                ],
                'submitted_at' => $application->submitted_at?->toISOString(),
                'decided_at' => $application->decided_at?->toISOString(),
                'timeline' => (new ApplicationResource($application))->timelineFor($visibleStatus),
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
