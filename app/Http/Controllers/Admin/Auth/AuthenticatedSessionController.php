<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Handle an incoming authentication request.
     */
   public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    // Check if account is disabled
    if (Auth::user()->status !== 'active') {  // Adjust if your column uses 'disabled', 0/1, etc.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'email' => 'Your account has been disabled. Please contact support.',
        ])->onlyInput('email');
    }

    // Check if user has admin, staff, or owner role
    if (!in_array(Auth::user()->role, ['admin', 'staff', 'owner'])) {
        Auth::logout();
        return back()->withErrors([
            'email' => 'You do not have access to the admin panel.',
        ])->onlyInput('email');
    }

    // Customize welcome message for different roles
    if (Auth::user()->role === 'admin') {
        $welcomeMessage = 'Login successful! Welcome, Admin.';
    } elseif (Auth::user()->role === 'owner') {
        $welcomeMessage = 'Login successful! Welcome, Owner.';
    } else {
        $welcomeMessage = 'Login successful! Welcome, ' . Auth::user()->name . '.';
    }

    // Redirect owners to Sales dashboard since admin dashboard is restricted for them
    if (Auth::user()->role === 'owner') {
        return redirect()->intended(route('admin.sales.dashboard'))->with('success', $welcomeMessage);
    }

    return redirect()->intended(route('admin.dashboard'))->with('success', $welcomeMessage);
}
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}