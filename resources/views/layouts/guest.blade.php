<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: $persist(false).as('darkMode') }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Analytics -->
    @if(config('services.google.analytics_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.google.analytics_id') }}');
    </script>
    @endif

    <title>{{ $title ?? config('branding.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1565c0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('branding.short_name') }}">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:600,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <!-- Logo -->
        <div class="mb-6">
            <a href="/" class="flex flex-col items-center gap-3">
                <img src="{{ config('branding.logo.default') }}" alt="{{ config('branding.name') }}" class="h-16 w-16 object-contain">
                <h1 class="text-center text-2xl font-bold text-primary-600 dark:text-primary-400 leading-tight max-w-xs"
                    style="font-family: 'Playfair Display', Georgia, serif;">
                    {{ config('branding.name') }}
                </h1>
            </a>
        </div>

        <!-- Flash Messages -->
        @if (session('status'))
            <div class="w-full sm:max-w-md mb-4">
                <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="w-full sm:max-w-md mb-4">
                <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg text-sm">
                    {!! session('error') !!}
                </div>
            </div>
        @endif

        <!-- Content -->
        <div class="w-full sm:max-w-md px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg border-t-4 border-[#c9a227]">
            {{ $slot }}
        </div>

        <!-- Dark Mode Toggle -->
        <div class="mt-6">
            <button @click="darkMode = !darkMode"
                    class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <svg x-show="!darkMode" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="darkMode" class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
        </div>
    </div>

    <script>
    // Refresh CSRF token when PWA resumes from background or page regains focus
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            fetch('/api/csrf-token', { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.token) {
                        document.querySelector('meta[name="csrf-token"]').content = data.token;
                        document.querySelectorAll('input[name="_token"]').forEach(function(el) {
                            el.value = data.token;
                        });
                    }
                })
                .catch(function() {});
        }
    });
    </script>
    @stack('scripts')
</body>
</html>
