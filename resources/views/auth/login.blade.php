<x-guest-layout>
    <x-slot name="title">Login - {{ config('branding.name') }}</x-slot>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Login to Your Account</h2>

    <!-- Session Status (success messages) -->
    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ accountType: null, checking: false }" @submit.prevent="
        fetch('/api/csrf-token', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.token) $el.querySelector('input[name=_token]').value = data.token;
                $el.submit();
            })
            .catch(() => $el.submit())
    ">
        @csrf

        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="label">Email Address</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   @blur="checkEmail($event.target.value)"
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        <!-- Account Type Indicator -->
        <div x-show="accountType" x-cloak class="mb-4 p-3 rounded-lg border" :class="{
            'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700': accountType === 'bidder',
            'bg-purple-50 dark:bg-purple-900 border-purple-200 dark:border-purple-700': accountType === 'auctioneer',
            'bg-yellow-50 dark:bg-yellow-900 border-yellow-200 dark:border-yellow-700': accountType === 'admin',
            'bg-red-50 dark:bg-red-900 border-red-200 dark:border-red-700': accountType === 'none'
        }">
            <p class="text-sm">
                <template x-if="accountType === 'bidder'">
                    <span class="text-blue-800 dark:text-blue-200">
                        🛍️ <strong>Bidder Account</strong> - You'll access your bidding dashboard
                    </span>
                </template>
                <template x-if="accountType === 'auctioneer'">
                    <span class="text-purple-800 dark:text-purple-200">
                        🔨 <strong>Auctioneer Account</strong> - You'll access your seller dashboard
                    </span>
                </template>
                <template x-if="accountType === 'admin'">
                    <span class="text-yellow-800 dark:text-yellow-200">
                        ⚙️ <strong>Administrator Account</strong> - You'll access the admin panel
                    </span>
                </template>
                <template x-if="accountType === 'none'">
                    <span class="text-red-800 dark:text-red-200">
                        ❌ <strong>No account found</strong> - Please check your email or <a href="{{ route('register') }}" class="underline font-semibold">create an account</a>
                    </span>
                </template>
            </p>
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="label">Password</label>
            <x-password-input name="password" id="password" :required="true" autocomplete="current-password" />
            @error('password')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
            </label>
        </div>

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-700">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="btn btn-primary w-full">
            Login
        </button>
    </form>

    <div class="mt-6 text-center">
        @php
            // Forward any intended URL (set by auth middleware before bouncing
            // here) onto the register link so the user lands back on the original
            // destination after signing up rather than the default dashboard.
            $forwardIntent = session('url.intended');
            $forwardIntent = ($forwardIntent && str_starts_with($forwardIntent, url('/'))) ? $forwardIntent : null;
            $registerHref = $forwardIntent
                ? route('register') . '?redirect=' . urlencode($forwardIntent)
                : route('register');
        @endphp
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Don't have an account?
            <a href="{{ $registerHref }}" class="text-primary-600 hover:text-primary-700 font-medium">Create Account</a>
        </p>
    </div>

    @push('scripts')
    <script>
    // Alpine.js function to check email
    function checkEmail(email) {
        if (!email || !email.includes('@')) {
            this.accountType = null;
            return;
        }

        this.checking = true;

        fetch(`/api/check-email?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    this.accountType = data.role;
                } else {
                    this.accountType = 'none';
                }
                this.checking = false;
            })
            .catch(error => {
                console.error('Failed to check email:', error);
                this.accountType = null;
                this.checking = false;
            });
    }
    </script>
    @endpush
</x-guest-layout>
