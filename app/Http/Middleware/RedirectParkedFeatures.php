<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The community and agent subsystems are PARKED in the standalone product.
 * Their routes stay registered so existing `route('community.*')` / `route('agent.*')`
 * calls in Blade don't throw — but this middleware redirects any attempt to actually
 * reach those URLs (typed, bookmarked, or via a stray link) back to the home page,
 * so users can't escape into the dormant community/agent pages. One chokepoint
 * instead of hunting every link. Admin paths (/admin/...) are unaffected.
 */
class RedirectParkedFeatures
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if (preg_match('#^(community|communities|agent|agents|become-an-agent|sell-in-community)(/.*)?$#', $path)) {
            return redirect('/');
        }

        return $next($request);
    }
}
