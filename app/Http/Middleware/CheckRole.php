<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user has any of the allowed roles
        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        // Staff with active membership can access auctioneer routes
        if ($user->isStaff() && in_array('auctioneer', $roles)) {
            $membership = $user->staffMembership;
            if ($membership && $membership->is_active) {
                return $next($request);
            }
        }

        // User doesn't have required role
        abort(403, 'Unauthorized access.');
    }
}
