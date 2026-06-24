<?php

namespace App\Http\Middleware;

use App\Models\TermsVersion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return $next($request);
        }

        // Admins bypass terms check
        if ($request->user()->isAdmin()) {
            return $next($request);
        }

        // Skip on API routes (polling), accept-terms page, logout, or public terms page
        if ($request->is('api/*') || $request->is('terms/accept') || $request->is('logout') || $request->is('terms')) {
            return $next($request);
        }

        // Cache the terms check in session to avoid DB queries on every page load.
        // Key includes latest terms ID so it auto-invalidates when new terms are published.
        $latestTermsId = \App\Models\TermsVersion::current()?->id ?? 0;
        $cacheKey = "terms_ok:{$request->user()->id}:{$latestTermsId}";
        if (!session($cacheKey)) {
            if (!$request->user()->hasAcceptedCurrentTerms()) {
                return redirect()->route('terms.accept');
            }
            session([$cacheKey => true]);
        }

        return $next($request);
    }
}
