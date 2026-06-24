<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuctioneerActivated
{
    /**
     * Handle an incoming request.
     *
     * Ensure the authenticated user is an auctioneer in good standing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admins can access all seller routes
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user is an auctioneer or staff
        if (!$user->isAuctioneer() && !$user->isStaff()) {
            abort(403, 'You must be an auctioneer to access this feature.');
        }

        // Resolve the auctioneer (works for both owners and staff)
        $auctioneer = $user->resolveAuctioneer();

        // Check if auctioneer profile exists
        if (!$auctioneer) {
            if ($user->isStaff()) {
                abort(403, 'Your staff account is not linked to an active auctioneer. Please contact the business owner.');
            }
            return redirect()->route('seller.profile')
                ->with('error', 'Please complete your auctioneer profile first.');
        }

        // Check if auctioneer is in good standing (not severely in debt)
        if (!$auctioneer->isInGoodStanding()) {
            if ($user->isStaff()) {
                abort(403, 'The auctioneer account is not in good standing. Please contact the business owner.');
            }
            return redirect()->route('seller.credits')
                ->with('error', 'Your account has a negative balance. Please purchase credits to continue. Minimum top-up: ' . formatCurrency(config('platform.pricing.minimum_deposit', 100)));
        }

        // Allow access even with 0 or slightly negative balance
        // They can create events but won't be able to go live without credits
        return $next($request);
    }
}
