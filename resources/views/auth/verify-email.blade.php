<x-guest-layout>
    <x-slot name="title">Verify Email</x-slot>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Verify Your Email</h2>

    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800 dark:text-blue-200">
            Thanks for signing up! Before getting started, please verify your email address by clicking the link we sent to <strong>{{ auth()->user()->email }}</strong>.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
            <p class="text-sm text-green-800 dark:text-green-200">
                A new verification link has been sent to your email address.
            </p>
        </div>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-full">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline w-full">
                Logout
            </button>
        </form>
    </div>
</x-guest-layout>
