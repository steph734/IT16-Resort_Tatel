<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Audit_Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the admin login view.
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Log an authentication-related event
     */
    protected function logAuthEvent(string $action, string $description, Request $request, ?User $user = null): void
    {
        Audit_Log::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent() ?? 'Unknown',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        // CASE 1: Email does not exist
        if (!$user) {
            $this->logAuthEvent(
                action: 'login_failed',
                description: "Failed login attempt - email not found: {$request->email}",
                request: $request,
                user: null
            );

            return back()->withErrors([
                'email' => 'Invalid credentials.'
            ])->withInput($request->only('email'));
        }

        // CASE 2: Account is currently locked
        if ($user->locked_until && Carbon::now()->lt($user->locked_until)) {
            $seconds = Carbon::now()->diffInSeconds($user->locked_until);

            $this->logAuthEvent(
                action: 'login_blocked',
                description: "Attempt to access locked account. Locked until: {$user->locked_until} (remaining ~{$seconds}s)",
                request: $request,
                user: $user
            );

            return back()->withErrors([
                'email' => "Account locked. Try again in {$seconds} seconds."
            ])->withInput($request->only('email'));
        }

        // CASE 3: Wrong password
        if (!Hash::check($request->password, $user->password)) {
            $now = Carbon::now();
            $user->failed_attempts += 1;
            $user->last_failed_login = $now;

            $description = "Failed login attempt. Current count: {$user->failed_attempts}";

            if ($user->failed_attempts >= 3) {
                $lockMinutes = $user->lock_level == 0 ? 3 : 5;
                $user->locked_until = $now->copy()->addMinutes($lockMinutes);
                $description .= " → ACCOUNT LOCKED for {$lockMinutes} minutes (level {$user->lock_level})";
                $user->failed_attempts = 0;
                $user->lock_level += 1;

                $action = 'account_locked';
                $errorMessage = "Account locked for {$lockMinutes} minutes due to too many failed attempts.";
            } else {
                $action = 'login_failed';
                $remaining = 3 - $user->failed_attempts;
                $description .= " ({$remaining} attempt(s) remaining)";
                $errorMessage = "Invalid credentials. {$remaining} attempt(s) remaining.";
            }

            $user->save();

            $this->logAuthEvent(
                action: $action,
                description: $description,
                request: $request,
                user: $user
            );

            return back()->withErrors([
                'email' => $errorMessage
            ])->withInput($request->only('email'));
        }

        // CASE 4: Successful login
        Auth::login($user);

        $request->session()->regenerate();

        // Reset lockout fields
        $user->update([
            'failed_attempts'   => 0,
            'locked_until'      => null,
            'last_failed_login' => null,
            'lock_level'        => 0,
        ]);

        $this->logAuthEvent(
            action: 'login_success',
            description: 'Successful login',
            request: $request,
            user: $user
        );

        return redirect()->route('admin.dashboard')
            ->with('success', 'Login Successful');
    }

    /**
     * Destroy an authenticated session (logout)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->logAuthEvent(
                action: 'logout',
                description: 'User logged out successfully',
                request: $request,
                user: $user
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}