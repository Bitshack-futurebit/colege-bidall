<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Auctioneer;
use App\Models\Agent;
use App\Models\AuctioneerFollower;
use App\Models\PromoCode;
use App\Models\StaffInvite;
use App\Models\StaffMember;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(Request $request)
    {
        if ($request->has('redirect')) {
            $redirect = $request->redirect;
            if (str_starts_with($redirect, url('/'))) {
                session()->put('url.intended', $redirect);
            }
        }

        return response(view('auth.login'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Redirect based on role
            $user = Auth::user();

            // Auto-follow auctioneer if logging in via invite link
            $refSlug = session('join_ref');
            if ($refSlug) {
                $refAuctioneer = Auctioneer::where('slug', $refSlug)->where('is_activated', true)->first();
                if ($refAuctioneer) {
                    AuctioneerFollower::firstOrCreate([
                        'user_id' => $user->id,
                        'auctioneer_id' => $refAuctioneer->id,
                    ]);
                }
                session()->forget('join_ref');
            }

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Welcome back, ' . $user->name . '!');
            }

            if ($user->isAuctioneer()) {
                // Safety check: ensure auctioneer profile exists
                if (!$user->auctioneer) {
                    // Profile missing - create it now with default values
                    $user->auctioneer()->create([
                        'business_name' => $user->name . "'s Auction House",
                        'slug' => \Str::slug($user->name) . '-' . $user->id . '-' . time(),
                        'whatsapp_number' => $user->phone ?? '',
                        'credit_balance' => 0,
                        'is_activated' => true,
                        'activated_at' => now(),
                    ]);

                    return redirect()->route('seller.profile')
                        ->with('warning', 'Welcome! Please complete your auctioneer profile to get started.');
                }

                return redirect()->intended(route('seller.dashboard'))
                    ->with('success', 'Welcome back, ' . $user->name . '!');
            }

            if ($user->isStaff()) {
                $membership = $user->staffMembership;
                if (!$membership || !$membership->is_active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')
                        ->withErrors(['email' => 'Your staff account has been deactivated. Please contact the business owner.']);
                }

                return redirect()->intended(route('seller.dashboard'))
                    ->with('success', 'Welcome back, ' . $user->name . '!');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        // Login failed - provide helpful error messages
        $userExists = User::where('email', $credentials['email'])->exists();

        if ($userExists) {
            return back()->withErrors([
                'password' => 'Incorrect password. Try again or click "Forgot password?" below.',
            ])->onlyInput('email');
        } else {
            return back()->withErrors([
                'email' => 'No account found with this email. Please check your email or create an account.',
            ])->onlyInput('email');
        }
    }

    /**
     * Show bidder registration form.
     */
    public function showRegister()
    {
        return response(view('auth.register'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Handle unified registration (bidder or auctioneer).
     */
    public function register(Request $request)
    {
        // Base validation (all users)
        $isBidder = $request->role === 'bidder';
        $rules = [
            'role' => ['required', 'in:bidder,auctioneer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'address' => [$isBidder ? 'nullable' : 'required', 'string'],
            'city' => [$isBidder ? 'nullable' : 'required', 'string', 'max:100'],
            'province' => [$isBidder ? 'nullable' : 'required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // Add auctioneer-specific validation
        if ($request->role === 'auctioneer') {
            $rules['business_name'] = ['required', 'string', 'max:255'];
            $rules['whatsapp_number'] = ['required', 'string', 'max:20'];
            $rules['promo_code'] = ['nullable', 'string', 'max:50'];
        }

        // Accept client-side geocoded coordinates
        $rules['lat'] = ['nullable', 'numeric', 'between:-90,90'];
        $rules['lng'] = ['nullable', 'numeric', 'between:-180,180'];

        $rules['accept_terms'] = ['required', 'accepted'];

        $validated = $request->validate($rules, [
            'accept_terms.required' => 'You must accept the Terms & Conditions.',
            'accept_terms.accepted' => 'You must accept the Terms & Conditions.',
        ]);

        // Use client-side coordinates if provided, otherwise try server-side geocoding
        if (!empty($validated['lat']) && !empty($validated['lng'])) {
            $coordinates = ['lat' => $validated['lat'], 'lng' => $validated['lng']];
        } elseif (!empty($validated['city'])) {
            $coordinates = $this->geocodeAddress(
                $validated['address'] ?? '',
                $validated['city'],
                $validated['province'] ?? ''
            );
            if ($coordinates['lat'] === null) {
                \Log::warning("Geocoding failed for registration: {$validated['city']}, {$validated['province']}");
            }
        } else {
            $coordinates = ['lat' => null, 'lng' => null];
        }

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
        ]);

        // Record terms acceptance (general + role-specific)
        $termsToAccept = TermsVersion::currentForUser($user);
        foreach ($termsToAccept as $terms) {
            TermsAcceptance::create([
                'user_id' => $user->id,
                'terms_version_id' => $terms->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accepted_at' => now(),
            ]);
        }

        // If auctioneer, create auctioneer profile
        if ($validated['role'] === 'auctioneer') {
            $auctioneerData = [
                'business_name' => $validated['business_name'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'slug' => \Str::slug($validated['business_name']) . '-' . $user->id,
                'credit_balance' => 0,
                'is_activated' => true,
                'activated_at' => now(),
            ];

            // Apply promo code if provided
            $promoCode = null;
            if (!empty($validated['promo_code'])) {
                $promoCode = PromoCode::where('code', strtoupper(trim($validated['promo_code'])))->first();

                if (!$promoCode || !$promoCode->isValid()) {
                    return back()->withInput()->withErrors(['promo_code' => 'Invalid or expired promo code.']);
                }

                // Copy pricing settings from promo code to auctioneer
                $auctioneerData['promo_code_id'] = $promoCode->id;
                $auctioneerData['is_free_account'] = $promoCode->is_free_account;
                if ($promoCode->custom_lot_fee !== null) {
                    $auctioneerData['custom_lot_fee'] = $promoCode->custom_lot_fee;
                }
                if ($promoCode->custom_tier_basic !== null) {
                    $auctioneerData['custom_tier_basic'] = $promoCode->custom_tier_basic;
                }
                if ($promoCode->custom_tier_pro !== null) {
                    $auctioneerData['custom_tier_pro'] = $promoCode->custom_tier_pro;
                }
                if ($promoCode->custom_tier_premium !== null) {
                    $auctioneerData['custom_tier_premium'] = $promoCode->custom_tier_premium;
                }
                if ($promoCode->free_relist_reset) {
                    $auctioneerData['free_relist_reset'] = $promoCode->free_relist_reset;
                }
            }

            $auctioneer = $user->auctioneer()->create($auctioneerData);

            // Apply bonus credits and increment usage
            if ($promoCode) {
                if ($promoCode->bonus_credits > 0) {
                    $auctioneer->addCredits(
                        (float) $promoCode->bonus_credits,
                        'adjustment',
                        'Promo code bonus: ' . $promoCode->code
                    );
                }
                $promoCode->increment('times_used');
            }
        }

        event(new Registered($user));

        Auth::login($user);

        // Auto-follow auctioneer if registering via invite link
        $refSlug = $request->input('ref') ?? session('join_ref');
        if ($refSlug) {
            $refAuctioneer = Auctioneer::where('slug', $refSlug)->where('is_activated', true)->first();
            if ($refAuctioneer) {
                AuctioneerFollower::firstOrCreate([
                    'user_id' => $user->id,
                    'auctioneer_id' => $refAuctioneer->id,
                ]);
            } else {
                // Try as agent referral code
                $refAgent = Agent::where('referral_code', strtoupper($refSlug))->where('status', 'active')->first();
                if ($refAgent) {
                    $user->update(['referred_by_agent_id' => $refAgent->id]);
                }
            }
            session()->forget('join_ref');
        }

        // Auto-join community region if registering from a community page
        $communitySlug = $request->input('community');
        if ($communitySlug && $user->isBidder()) {
            $communityRegion = \App\Models\CommunityRegion::where('slug', $communitySlug)
                ->where('is_active', true)
                ->first();
            if ($communityRegion) {
                $user->update([
                    'community_region_id' => $communityRegion->id,
                    'community_region_changed_at' => now(),
                ]);
            }
        }

        // Check for pending redirect
        $redirect = $request->input('redirect');
        if ($redirect && str_starts_with($redirect, url('/'))) {
            return redirect($redirect)
                ->with('success', 'Welcome to the BidAll family, ' . $user->name . '!')
                ->with('push_prompt', true);
        }

        // Redirect based on role
        if ($user->isAuctioneer()) {
            return redirect()->route('seller.credits')
                ->with('success', 'Welcome to the BidAll family, ' . $user->name . '! Purchase credits to start creating auctions. Minimum deposit: ' . formatCurrency(config('platform.pricing.minimum_deposit', 100)))
                ->with('push_prompt', true);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to the BidAll family, ' . $user->name . '!')
            ->with('push_prompt', true);
    }

    /**
     * Show seller (auctioneer) registration form.
     */
    public function showSellerRegister()
    {
        return view('auth.register-seller');
    }

    /**
     * Handle seller registration.
     */
    public function registerSeller(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'password' => $request->password,
            'role' => 'auctioneer',
        ]);

        // Create auctioneer profile
        Auctioneer::create([
            'user_id' => $user->id,
            'business_name' => $request->business_name,
            'whatsapp_number' => $request->whatsapp_number,
            'is_activated' => true,
            'activated_at' => now(),
            'credit_balance' => 0,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('seller.credits')
            ->with('success', 'Welcome to the BidAll family, ' . $user->name . '! Purchase credits to start creating auctions. Minimum deposit: ' . formatCurrency(config('platform.pricing.minimum_deposit', 100)))
            ->with('push_prompt', true);
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        // Preserve white-label context across logout so branded users return to the branded site.
        $whiteLabelSlug = $request->session()->get('white_label_slug');

        // Remove browser push subscriptions tied to this user so follower-targeted pushes
        // don't keep hitting this device after a different user logs in (shared-device leak).
        if ($user = Auth::user()) {
            \App\Models\PushSubscription::where('user_id', $user->id)->delete();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($whiteLabelSlug) {
            $auctioneer = \App\Models\Auctioneer::where('slug', $whiteLabelSlug)->first();
            if ($auctioneer && $auctioneer->isWhiteLabel()) {
                $request->session()->put('white_label_slug', $whiteLabelSlug);
                return redirect()->route('auctioneer.show', $whiteLabelSlug);
            }
        }

        return redirect()->route('home');
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show reset password form.
     */
    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->email))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('info', 'Email already verified.');
        }

        $user->markEmailAsVerified();

        return redirect()->route('dashboard')->with('success', 'Email verified successfully!');
    }

    /**
     * Resend email verification notification.
     */
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('info', 'Email already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent!');
    }

    /**
     * Show staff registration form.
     */
    public function showStaffRegister(string $token)
    {
        $invite = StaffInvite::where('token', $token)->first();

        if (!$invite || !$invite->isValid()) {
            return redirect()->route('login')
                ->with('error', 'This invite link is invalid or has expired.');
        }

        $invite->load('auctioneer');
        $roleName = str_replace('_', ' ', $invite->staff_role);

        return view('auth.register-staff', compact('invite', 'roleName'));
    }

    /**
     * Handle staff registration.
     */
    public function registerStaff(Request $request, string $token)
    {
        $invite = StaffInvite::where('token', $token)->first();

        if (!$invite || !$invite->isValid()) {
            return redirect()->route('login')
                ->with('error', 'This invite link is invalid or has expired.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $rules['accept_terms'] = ['required', 'accepted'];

        $validated = $request->validate($rules, [
            'accept_terms.required' => 'You must accept the Terms & Conditions.',
            'accept_terms.accepted' => 'You must accept the Terms & Conditions.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => $validated['password'],
            'role' => 'staff',
        ]);

        // Record terms acceptance (general + role-specific)
        $termsToAccept = TermsVersion::currentForUser($user);
        foreach ($termsToAccept as $terms) {
            TermsAcceptance::create([
                'user_id' => $user->id,
                'terms_version_id' => $terms->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accepted_at' => now(),
            ]);
        }

        StaffMember::create([
            'user_id' => $user->id,
            'auctioneer_id' => $invite->auctioneer_id,
            'staff_role' => $invite->staff_role,
            'invited_by' => $invite->invited_by,
        ]);

        $invite->update([
            'used_at' => now(),
            'used_by' => $user->id,
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('seller.dashboard')
            ->with('success', 'Welcome to the BidAll family, ' . $user->name . '! You have joined as staff.')
            ->with('push_prompt', true);
    }

    /**
     * Geocode an address to get latitude and longitude coordinates.
     * Uses OpenStreetMap Nominatim API (free, no API key required).
     */
    private function geocodeAddress(string $address, string $city, string $province): array
    {
        try {
            // Build full address for South Africa
            $fullAddress = urlencode("{$address}, {$city}, {$province}, South Africa");

            // Call Nominatim API
            $url = "https://nominatim.openstreetmap.org/search?q={$fullAddress}&format=json&limit=1&countrycodes=za";

            // Set user agent (required by Nominatim)
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: " . config('branding.name', 'BasicBidall') . " Auction Platform\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                // Geocoding failed, return default coordinates (center of South Africa)
                return ['lat' => null, 'lng' => null];
            }

            $data = json_decode($response, true);

            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                ];
            }

            // No results found, try city/province only
            $cityAddress = urlencode("{$city}, {$province}, South Africa");
            $url = "https://nominatim.openstreetmap.org/search?q={$cityAddress}&format=json&limit=1&countrycodes=za";
            $response = @file_get_contents($url, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lng' => (float) $data[0]['lon'],
                    ];
                }
            }

            // Fallback to center of South Africa
            return ['lat' => null, 'lng' => null];

        } catch (\Exception $e) {
            // On any error, return default coordinates
            \Log::warning("Geocoding failed: " . $e->getMessage());
            return ['lat' => null, 'lng' => null];
        }
    }
}
