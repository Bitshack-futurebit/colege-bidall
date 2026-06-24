<x-guest-layout>
    <x-slot name="title">Create Account - {{ config('branding.name') }}</x-slot>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Create Account</h2>

    @if(request('ref'))
        <div class="mb-6 p-4 bg-primary-50 dark:bg-primary-900 border border-primary-200 dark:border-primary-800 rounded-lg text-center">
            <p class="text-sm text-primary-700 dark:text-primary-300">You've been invited to join {{ config('branding.name') }}! Register below and you'll automatically follow this auctioneer.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <input type="hidden" name="role" value="bidder">
        @if(request('ref'))
            <input type="hidden" name="ref" value="{{ request('ref') }}">
        @endif
        @if(request('community'))
            <input type="hidden" name="community" value="{{ request('community') }}">
        @endif
        @php
            $redirectAfterRegister = request('redirect') ?: session('url.intended');
            if ($redirectAfterRegister && !str_starts_with($redirectAfterRegister, url('/'))) {
                $redirectAfterRegister = null;
            }
        @endphp
        @if($redirectAfterRegister)
            <input type="hidden" name="redirect" value="{{ $redirectAfterRegister }}">
        @endif

        <div class="mb-4">
            <label for="name" class="label">Profile Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="input @error('name') input-error @enderror">
            @error('name')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="input @error('email') input-error @enderror">
            @error('email')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="whatsapp" class="label">WhatsApp Number</label>
            <input id="whatsapp" type="tel" name="whatsapp" value="{{ old('whatsapp') }}" required autocomplete="tel" class="input @error('whatsapp') input-error @enderror">
            @error('whatsapp')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="password" class="label">Password</label>
            <x-password-input name="password" id="password" :required="true" autocomplete="new-password" />
            @error('password')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="label">Confirm Password</label>
            <x-password-input name="password_confirmation" id="password_confirmation" :required="true" autocomplete="new-password" />
        </div>

        <div class="mb-6">
            <label class="flex items-start gap-2 cursor-pointer">
                <input type="checkbox" name="accept_terms" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" {{ old('accept_terms') ? 'checked' : '' }}>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    I agree to the <a href="{{ route('terms') }}" target="_blank" class="text-primary-600 hover:text-primary-700 underline">Terms & Conditions</a>
                </span>
            </label>
            @error('accept_terms')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-full">Create Account</button>
    </form>

    <div class="mt-6 text-center space-y-2">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Already have an account? <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-medium">Log in</a>
        </p>
        {{-- "Register as Auctioneer" public link PARKED — public registration is bidder-only.
             The single owner-auctioneer is created out-of-band via the unlinked
             register.seller route (or admin). --}}
    </div>
</x-guest-layout>
