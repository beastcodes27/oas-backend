<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateCookieToken;
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
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->respondAuthenticated($request, $user, 'Account created successfully.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->respondAuthenticated($request, $user, 'Logged in successfully.');
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

        return $this->respondAuthenticated($request, $user, 'Account created successfully.', 201);
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

        return $this->respondAuthenticated($request, $user, 'Logged in successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user() !== null) {
            $request->user()->currentAccessToken()?->delete();
        }

        $response = response()->json(['message' => 'Logged out successfully.']);
        $response->headers->setCookie($this->authCookie('', expire: true));

        return $response;
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

    /**
     * Build a successful auth response. For requests coming from the SPA the
     * token is only written to an httpOnly cookie (out of reach of scripts);
     * external clients still receive the plaintext token in the body.
     */
    private function respondAuthenticated(Request $request, User $user, string $message, int $status = 200): JsonResponse
    {
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = response()->json([
            'message' => $message,
            'user' => new UserResource($user),
        ], $status);

        if ($this->isFrontendRequest($request)) {
            $response->headers->setCookie($this->authCookie($token));
        } else {
            $payload = $response->getData(true);
            $payload['token'] = $token;
            $response->setData($payload);
        }

        return $response;
    }

    /**
     * Determine whether the request originates from the first-party SPA.
     */
    private function isFrontendRequest(Request $request): bool
    {
        return EnsureFrontendRequestsAreStateful::fromFrontend($request);
    }

    /**
     * Build the httpOnly authentication cookie.
     */
    private function authCookie(string $token, bool $expire = false): Cookie
    {
        $session = config('session');
        $minutes = $expire
            ? -2628000 // one month in the past — expires the cookie immediately
            : (int) config('sanctum.expiration', 10080);

        return Cookie::create(
            AuthenticateCookieToken::COOKIE_NAME,
            $expire ? '' : $token,
            $minutes,
            $session['path'] ?? '/',
            $session['domain'],
            (bool) ($session['secure'] ?? false),
            true, // httpOnly — JavaScript can never read the token
            false,
            $session['same_site'] ?: 'lax',
        );
    }
}
