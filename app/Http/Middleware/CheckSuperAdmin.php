<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the authenticated user is a super admin
        if (Auth::check() && Auth::user()->super_admin == 'Y') {
            return $next($request);
        }

        // Redirect if not a super admin
        return redirect()->route('home')->with('error', 'You do not have permission to access this page.');
    }
}
