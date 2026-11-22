<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Sign In - KYAMCH IPAM')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectIntended(route('dashboard'));
        }
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($this->email) . '|' . request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditEvent::record('auth.rate_limited', "Too many failed attempts for {$this->email}. Locked for {$seconds}s");

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 300);

            AuditEvent::record(
                'auth.failed',
                'Invalid credentials submitted for email: ' . $this->email
            );

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our authorized records.',
            ]);
        }

        if (! $user->isActive()) {
            Auth::logout();
            AuditEvent::record('auth.blocked_inactive', 'Attempted login to inactive account: ' . $this->email, $user);

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact IT Security Administration.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        AuditEvent::record(
            'auth.login',
            'Operator authenticated successfully. Role: ' . $user->role->value,
            $user
        );

        $this->redirectIntended(route('dashboard'));
    }

    /**
     * Helper for development/testing: pre-fills credentials
     */
    public function fillAccount(string $role): void
    {
        $map = [
            'admin' => 'admin@kyamch.local',
            'netadmin' => 'netadmin@kyamch.local',
            'operator' => 'operator@kyamch.local',
            'auditor' => 'auditor@kyamch.local',
            'viewer' => 'viewer@kyamch.local',
        ];

        if (isset($map[$role])) {
            $this->email = $map[$role];
            $this->password = 'Password123!';
        }
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
