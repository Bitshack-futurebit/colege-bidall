<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->is_active) {
            $reason = Auth::user()->suspension_reason;
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $supportUrl = config('regional.whatsapp.support_group_url', '#');
            $message = $reason
                ? 'Your account has been suspended: ' . e($reason) . '. <a href="' . $supportUrl . '" target="_blank" class="underline font-semibold">Contact Support</a> for assistance.'
                : 'Your account has been suspended. <a href="' . $supportUrl . '" target="_blank" class="underline font-semibold">Contact Support</a> for assistance.';

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
