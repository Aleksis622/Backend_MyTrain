<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetUserLanguage;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Cookie-based (Sanctum SPA) authentication.
 *
 * The frontend must call GET /sanctum/csrf-cookie first and send requests
 * from a SANCTUM_STATEFUL_DOMAINS origin with credentials enabled.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $this->ensureSession($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'language' => ['nullable', Rule::in(SetUserLanguage::SUPPORTED)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'language' => $data['language'] ?? SetUserLanguage::DEFAULT,
        ]);

        // Sends the verification email. A mail server problem must not block registration.
        try {
            event(new Registered($user));
        } catch (Throwable $e) {
            report($e);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $this->ensureSession($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'user' => Auth::guard('web')->user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function changeLanguage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'language' => ['required', Rule::in(SetUserLanguage::SUPPORTED)],
        ]);

        $user = $request->user();
        $user->update(['language' => $data['language']]);

        return response()->json([
            'message' => __('language_changed'),
            'language' => $user->language,
        ]);
    }

    /**
     * Requests that are not "stateful" (e.g. Postman without an Origin/Referer
     * from SANCTUM_STATEFUL_DOMAINS) have no session to log into.
     */
    private function ensureSession(Request $request): void
    {
        if (! $request->hasSession()) {
            abort(response()->json([
                'error' => 'No session. Call the API from a SANCTUM_STATEFUL_DOMAINS origin (e.g. http://localhost:5173) with credentials enabled.',
            ], 400));
        }
    }
}
