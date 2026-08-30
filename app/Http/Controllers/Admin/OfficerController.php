<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetOfficerPasswordRequest;
use App\Http\Requests\StoreOfficerRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficerController extends Controller
{
    /**
     * List all admission officer accounts.
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()
                ->where('is_admissions', true)
                ->where('is_admin', false)
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Create a new admission officer account.
     */
    public function store(StoreOfficerRequest $request): UserResource
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'is_admissions' => true,
            'is_admin' => false,
        ]);

        return new UserResource($user);
    }

    /**
     * Reset an admission officer's password.
     */
    public function resetPassword(ResetOfficerPasswordRequest $request, User $user): JsonResponse
    {
        if (! $user->is_admissions || $user->is_admin) {
            abort(404);
        }

        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Password reset successfully.']);
    }

    /**
     * Remove an admission officer account.
     */
    public function destroy(User $user): JsonResponse
    {
        if (! $user->is_admissions || $user->is_admin) {
            abort(404);
        }

        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot remove your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Admission officer removed.']);
    }
}
