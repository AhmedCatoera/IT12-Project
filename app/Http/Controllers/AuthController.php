<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // At most 5 wrong tries per minute for the same email and device.
        $key = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'email' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ])->onlyInput('email');
        }

        // No "remember me": the session ends after 2 hours of inactivity (Security Plan).
        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }

        RateLimiter::clear($key);

        if (! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Your account is deactivated. Please contact the Owner.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
