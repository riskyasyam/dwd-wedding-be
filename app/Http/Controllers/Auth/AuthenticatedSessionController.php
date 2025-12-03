<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * Supports both web (session) and API (token) authentication.
     */
    public function store(Request $request)
    {
        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            // API Login - Return JSON with token
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        }

        // Web Login - Use session
        $loginRequest = app(LoginRequest::class);
        $loginRequest->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Redirect based on role
        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     * Supports both web (session) and API (token) authentication.
     */
    public function destroy(Request $request)
    {
        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            // API Logout - Revoke token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        }

        // Web Logout - Destroy session
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}