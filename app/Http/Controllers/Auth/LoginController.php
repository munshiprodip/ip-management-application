<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Handle standard HTTP POST authentication request (failsafe fallback for Livewire/JS).
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($validated['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditEvent::record('auth.rate_limited', "Too many failed attempts for {$validated['email']}. Locked for {$seconds}s");

            return back()->withInput()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $validated['email'])->first();
        $remember = $request->boolean('remember');

        if (! $user || ! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember)) {
            RateLimiter::hit($throttleKey, 300);

            AuditEvent::record('auth.failed', 'Invalid credentials submitted for email: ' . $validated['email']);

            return back()->withInput()->withErrors([
                'email' => 'These credentials do not match our authorized records.',
            ]);
        }

        if (! $user->isActive()) {
            Auth::logout();
            AuditEvent::record('auth.blocked_inactive', 'Attempted login to inactive account: ' . $validated['email'], $user);

            return back()->withInput()->withErrors([
                'email' => 'This account has been deactivated. Please contact IT Security Administration.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        AuditEvent::record(
            'auth.login',
            'Operator authenticated successfully. Role: ' . $user->role->value,
            $user
        );

        $request->session()->flash('success', "Welcome back, {$user->name}. Signed in as {$user->role->value}.");

        return redirect()->intended(route('dashboard'));
    }
}

