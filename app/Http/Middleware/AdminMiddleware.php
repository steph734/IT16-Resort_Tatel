<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            // Redirect to login if not authenticated
            return redirect()->route('admin.login')
                ->with('message', 'Your session has expired. Please log in again.')
                ->with('status', 'warning');
        }

        // Check if user has admin, staff, or owner role
        if (!in_array(Auth::user()->role, ['admin', 'staff', 'owner'])) {
            Auth::logout();
            return redirect()->route('admin.login')->with('error', 'You do not have access to the admin panel.');
        }

        return $next($request);
    }
}