<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class NotSuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        // Block owner role from accessing wrapped routes
        if (Auth::user()->role === 'owner') {
            abort(403, 'Access denied. Owners may only access Sales and Settings.');
        }

        return $next($request);
    }
}
