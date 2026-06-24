<x-guest-layout>
    <x-slot name="title">Forgot Password</x-slot>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Forgot Password</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Enter your email and we'll send you a password reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input @error('email') input-error @enderror">
            @error('email')<p class="error-message">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-primary-600 hover:text-primary-700">Back to Login</a>
    </div>
</x-guest-layout>
