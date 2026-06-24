<x-guest-layout>
    <x-slot name="title">Register as Auctioneer</x-slot>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Register as Auctioneer</h2>

    <form method="POST" action="{{ route('register.seller') }}">
        @csrf

        <div class="mb-4">
            <label for="name" class="label">Profile Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required class="input @error('name') input-error @enderror">
            @error('name')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror">
            @error('email')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="business_name" class="label">Business Name</label>
            <input id="business_name" type="text" name="business_name" value="{{ old('business_name') }}" required class="input @error('business_name') input-error @enderror">
            @error('business_name')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="whatsapp" class="label">WhatsApp Number</label>
            <input id="whatsapp" type="text" name="whatsapp" value="{{ old('whatsapp') }}" required class="input @error('whatsapp') input-error @enderror">
            @error('whatsapp')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="whatsapp_number" class="label">WhatsApp Number</label>
            <input id="whatsapp_number" type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" required class="input @error('whatsapp_number') input-error @enderror">
            <p class="text-xs text-gray-500 mt-1">Used for buyer inquiries</p>
            @error('whatsapp_number')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="address" class="label">Address (Optional)</label>
            <input id="address" type="text" name="address" value="{{ old('address') }}" class="input">
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="city" class="label">City</label>
                <input id="city" type="text" name="city" value="{{ old('city') }}" class="input">
            </div>
            <div>
                <label for="province" class="label">Province</label>
                <input id="province" type="text" name="province" value="{{ old('province') }}" class="input">
            </div>
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

        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                After registration, you'll need to pay a one-time activation fee of <strong>{{ formatPrice('platform.pricing.activation_fee') }}</strong> to start creating events.
            </p>
        </div>

        <button type="submit" class="btn btn-primary w-full">Register as Auctioneer</button>
    </form>

    <div class="mt-6 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Already have an account? <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-medium">Login</a>
        </p>
    </div>
</x-guest-layout>
