<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
     */
  public function store(LoginRequest $request): RedirectResponse
{
    $request->validate([
        'g-recaptcha-response' => [
            'required',
            function ($attribute, $value, $fail) use ($request) {   // ← add this
                $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => env('RECAPTCHA_SECRET_KEY'),
                    'response' => $value,
                    'remoteip' => $request->ip(),
                ]);

                $body = $response->json();

                if (!$body['success']) {
                    $fail('reCAPTCHA verification failed. Please try again.');
                }
            },
        ],
    ]);

    $request->authenticate();

    $request->session()->regenerate();

    // Optional admin check
    if (Auth::user()?->role !== 'admin') {
        Auth::logout();
        return redirect()->route('admin.login')
            ->withErrors(['email' => 'You do not have admin access.']);
    }

    return redirect()->intended(route('dashboard', absolute: false));
}
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
