<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionInactivity
{
    /**
     * Handle an incoming request.
     * If user is authenticated, check last activity timestamp stored in session.
     * If inactivity is greater than configured threshold, log out and invalidate session.
     */
    public function handle(Request $request, Closure $next): Response
    {
    // Defensive: only enforce inactivity on admin routes (URL or named route)
    // and for authenticated admin/staff/owner users. This prevents
    // accidental application of the middleware to guest/public pages.
    $isAdminPath = $request->is('admin/*') || $request->routeIs('admin.*');

    if ($isAdminPath && Auth::check() && in_array(Auth::user()->role ?? null, ['admin', 'staff', 'owner'], true)) {
            // timeout in seconds: prefer explicit ADMIN_INACTIVITY_TIMEOUT_SECONDS env;
            // fallback to INACTIVITY_TIMEOUT_SECONDS, and finally to session.lifetime (minutes) * 60
            $timeoutSeconds = (int) env('ADMIN_INACTIVITY_TIMEOUT_SECONDS', env('INACTIVITY_TIMEOUT_SECONDS', config('session.lifetime', 15) * 60));

            $last = $request->session()->get('last_activity_time');
            $now = time();

            if ($last && ($now - $last) > $timeoutSeconds) {
                // user has been idle: logout and invalidate session
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Session expired due to inactivity.'], 401);
                }

                // Redirect explicitly to the admin login path. Using an explicit
                // path avoids potential route resolution issues and ensures the
                // user is returned to /admin/login after being logged out.
                return redirect()->guest('/admin/login')
                    ->with('message', 'You were logged out due to inactivity.')
                    ->with('status', 'warning');
            }

            // update last activity
            $request->session()->put('last_activity_time', $now);
        }

        return $next($request);
    }
}
