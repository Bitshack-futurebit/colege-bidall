<x-guest-layout>
    <x-slot name="title">Join as Staff - {{ config('branding.name') }}</x-slot>

    <div class="text-center mb-6">
        @if($invite->auctioneer->logo)
            <img src="{{ Storage::disk('public')->url($invite->auctioneer->logo) }}" alt="{{ $invite->auctioneer->business_name }}" class="h-16 w-16 rounded-full mx-auto mb-3 object-cover">
        @endif
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Join as Staff</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            <span class="font-semibold">{{ $invite->auctioneer->business_name }}</span> has invited you as
        </p>
        <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-semibold bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300">
            {{ ucwords($roleName) }}
        </span>
    </div>

    <form method="POST" action="{{ route('register.staff.store', $invite->token) }}">
        @csrf

        <div class="mb-4">
            <label for="name" class="label">Profile Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="input @error('name') border-red-500 @enderror">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="label">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="input @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="whatsapp" class="label">WhatsApp Number</label>
            <input id="whatsapp" type="tel" name="whatsapp" value="{{ old('whatsapp') }}" required class="input @error('whatsapp') border-red-500 @enderror">
            @error('phone')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="label">Password</label>
            <x-password-input name="password" id="password" :required="true" autocomplete="new-password" errorClass="border-red-500" />
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
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

        <button type="submit" class="btn btn-primary w-full">
            Create Account & Join as Staff
        </button>

        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
            Already have an account? <a href="{{ route('login') }}" class="text-primary-600 dark:text-primary-400 hover:underline">Login</a>
        </p>
    </form>
</x-guest-layout>
