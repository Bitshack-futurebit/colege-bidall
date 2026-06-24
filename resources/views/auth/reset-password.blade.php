<x-guest-layout>
    <x-slot name="title">Reset Password</x-slot>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Reset Password</h2>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="mb-4">
            <label for="password" class="label">New Password</label>
            <x-password-input name="password" id="password" :required="true" autocomplete="new-password" />
            @error('password')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="label">Confirm Password</label>
            <x-password-input name="password_confirmation" id="password_confirmation" :required="true" autocomplete="new-password" />
        </div>

        <button type="submit" class="btn btn-primary w-full">Reset Password</button>
    </form>
</x-guest-layout>
