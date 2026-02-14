<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use App\Models\User;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate input
        $request->validate([
            'account_id' => ['required', 'string'],
        ]);

        // Find user by account ID or email
        $user = User::where('user_id', $request->account_id)
                    ->orWhere('email', $request->account_id)
                    ->first();

        // Allow admin or owner to request password reset via admin panel
        if (!$user || !in_array($user->role, ['admin', 'owner'])) {
            return back()->withInput($request->only('account_id'))
                        ->withErrors(['account_id' => 'Invalid account ID or insufficient privileges.']);
        }

        // Use Laravel's Password broker to send the reset link
        $status = Password::sendResetLink(
            ['email' => $user->email] // Password broker requires email
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Password reset link has been sent to your email address. Please check your inbox.')
            : back()->withErrors(['account_id' => __($status)]);
    }
}
