<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateLoginRequest;
use App\Http\Requests\CandidateRegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'message' => 'Account created successfully.',
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Create a candidate account keyed by the NECTA index number.
     */
    public function candidateRegister(CandidateRegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->username,
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'Account created successfully.',
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Sign a candidate in with their index number and password.
     */
    public function candidateLogin(CandidateLoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->username)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'index_number' => ['The index number or password is incorrect.'],
            ]);
        }

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Update the authenticated user's profile (name, phone, email, password).
     */
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = [];

        if ($request->filled('name')) {
            $data['name'] = $request->name;
        }

        if ($request->filled('phone')) {
            $data['phone'] = $request->phone;
        }

        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }

        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
            $data['password'] = $request->password;
        }

        if ($data !== []) {
            $user->update($data);
        }

        return new UserResource($user->fresh());
    }
}
