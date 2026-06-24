<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: $persist(false).as('darkMode') }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-authenticated" content="1">
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    @if(session('push_prompt'))
    <meta name="push-prompt" content="1">
    @endif

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

    @php
        $siteName = ($whiteLabel ?? null)?->isActive() ? $whiteLabel->businessName() : config('branding.name');
        $defaultOgImage = ($whiteLabel ?? null)?->isActive() && $whiteLabel->logoUrl()
            ? $whiteLabel->logoUrl()
            : asset('images/gavel-logo.svg');
    @endphp

    <title>{{ $title ?? $siteName }}</title>

    <!-- SEO Meta -->
    <meta name="description" content="{{ $description ?? config('branding.description') }}">
    <link rel="canonical" href="{{ $canonical ?? request()->url() }}">
    @if($noIndex ?? false)
    <meta name="robots" content="noindex, follow">
    @endif

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $description ?? config('branding.description') }}">
    <meta property="og:image" content="{{ $ogImage ?? $defaultOgImage }}">
    <meta property="og:image:alt" content="{{ $title ?? $siteName }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="900">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $siteName }}">
    <meta name="twitter:description" content="{{ $description ?? config('branding.description') }}">
    <meta name="twitter:image" content="{{ $ogImage ?? $defaultOgImage }}">

    <!-- Favicon -->
    @if(($whiteLabel ?? null)?->isActive() && $whiteLabel->faviconUrl())
    <link rel="icon" href="{{ $whiteLabel->faviconUrl() }}">
    @else
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @endif

    <!-- PWA -->
    @if(($whiteLabel ?? null)?->isActive())
    <link rel="manifest" href="{{ route('auctioneer.manifest', $whiteLabel->slug()) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('auctioneer.icon', ['auctioneer' => $whiteLabel->slug(), 'size' => 180]) }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ route('auctioneer.icon', ['auctioneer' => $whiteLabel->slug(), 'size' => 192]) }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ route('auctioneer.icon', ['auctioneer' => $whiteLabel->slug(), 'size' => 512]) }}">
    <link rel="apple-touch-startup-image" href="{{ route('auctioneer.icon', ['auctioneer' => $whiteLabel->slug(), 'size' => 512]) }}">
    @else
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    @endif
    <meta name="theme-color" content="{{ ($whiteLabel ?? null)?->isActive() ? $whiteLabel->primaryColor() : '#22c55e' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Brand palette — generated from config('branding.colors.primary') so the whole
         primary-* Tailwind scale re-themes at runtime (no asset rebuild). A live
         white-label context, if ever active, still overrides per-auctioneer. --}}
    @php
        $brandPrimary = ($whiteLabel ?? null)?->isActive()
            ? $whiteLabel->primaryColor()
            : config('branding.colors.primary', '#22c55e');
    @endphp
    <style>
        :root {
            @foreach(\App\Helpers\ColorHelper::generatePalette($brandPrimary) as $shade => $rgb)
            --color-primary-{{ $shade }}: {{ $rgb }};
            @endforeach
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased">
    <!-- Offline Indicator -->
    <div x-data="{ isOnline: true }"
         x-init="
            isOnline = navigator.onLine;
            window.addEventListener('online', () => isOnline = true);
            window.addEventListener('offline', () => isOnline = false);
         ">
        <div x-show="!isOnline"
             class="fixed top-0 left-0 right-0 bg-yellow-500 text-white py-2 px-4 text-center text-sm font-medium z-50 shadow-lg">
            <div class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>You're offline - Some features may be unavailable</span>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        @include('components.nav')

        <!-- Page Content — bottom padding on mobile to clear the sticky bottom nav -->
        <main class="pb-20 sm:pb-0">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg" role="alert">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg" role="alert">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @if (session('warning'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded-lg" role="alert">
                        {{ session('warning') }}
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg" role="alert">
                        {{ session('info') }}
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>

        <!-- Footer -->
        @include('components.footer')
    </div>

    <!-- PWA Install Prompt -->
    <div x-data="{
            showInstall: false,
            deferredPrompt: null,
            dismissed: localStorage.getItem('pwa-install-dismissed'),
            init() {
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    if (!this.dismissed) {
                        this.showInstall = true;
                    }
                });
                window.addEventListener('appinstalled', () => {
                    this.showInstall = false;
                    this.deferredPrompt = null;
                });
            },
            install() {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    this.deferredPrompt.userChoice.then((result) => {
                        this.deferredPrompt = null;
                        this.showInstall = false;
                    });
                }
            },
            dismiss() {
                this.showInstall = false;
                localStorage.setItem('pwa-install-dismissed', '1');
            }
         }"
         x-cloak>
        <div x-show="showInstall"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-full"
             class="fixed bottom-0 left-0 right-0 z-50 p-4 sm:bottom-6 sm:left-auto sm:right-6 sm:max-w-sm">
            @php
                $installIcon = ($whiteLabel ?? null)?->isActive()
                    ? route('auctioneer.icon', ['auctioneer' => $whiteLabel->slug(), 'size' => 192])
                    : '/icons/icon-192x192.png';
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start gap-3">
                    <img src="{{ $installIcon }}" alt="{{ $siteName }}" class="w-12 h-12 rounded-xl flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Install {{ $siteName }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Add to your home screen for quick access and a better experience.</p>
                    </div>
                    <button @click="dismiss()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 -mt-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="dismiss()" class="flex-1 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Not now
                    </button>
                    <button @click="install()" class="flex-1 px-3 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors font-medium">
                        Install
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <div x-data="{ showTop: false }"
         x-init="window.addEventListener('scroll', () => { showTop = window.scrollY > 300 })"
         x-cloak>
        <button x-show="showTop"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="fixed bottom-24 right-4 sm:bottom-6 sm:right-6 z-50 p-3 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>
    </div>

    <script>
    // Refresh CSRF token when PWA resumes from background
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

    // Fetch fresh CSRF token before form submit (prevents 419 on logout)
    function refreshCsrfAndSubmit(form) {
        fetch('/api/csrf-token', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.token) {
                    form.querySelector('input[name="_token"]').value = data.token;
                }
                form.onsubmit = null;
                form.submit();
            })
            .catch(function() {
                // Fallback: submit anyway with current token
                form.onsubmit = null;
                form.submit();
            });
        return false;
    }

    // Autosave long forms to localStorage — protects against PWA tab kills on mobile.
    // File inputs, _token and _method are skipped.
    window.setupFormAutosave = function(form, key) {
        if (!form || !key || typeof localStorage === 'undefined') return;
        var STORAGE_KEY = 'autosave:' + key;
        var SKIP = ['_token', '_method'];
        var isDirty = false;

        function serialize() {
            var data = { __savedAt: Date.now() };
            var els = form.querySelectorAll('input[name], textarea[name], select[name]');
            els.forEach(function(el) {
                if (el.type === 'file') return;
                if (SKIP.indexOf(el.name) !== -1) return;
                if (el.type === 'checkbox') {
                    if (!(el.name in data) || !Array.isArray(data[el.name])) data[el.name] = [];
                    if (el.checked) data[el.name].push(el.value);
                } else if (el.type === 'radio') {
                    if (el.checked) data[el.name] = el.value;
                } else {
                    data[el.name] = el.value;
                }
            });
            return data;
        }

        function restore(data) {
            var els = form.querySelectorAll('input[name], textarea[name], select[name]');
            els.forEach(function(el) {
                if (el.type === 'file') return;
                if (SKIP.indexOf(el.name) !== -1) return;
                if (!(el.name in data)) return;
                var val = data[el.name];
                if (el.type === 'checkbox') {
                    el.checked = Array.isArray(val) ? val.indexOf(el.value) !== -1 : (val === el.value || val === true);
                } else if (el.type === 'radio') {
                    el.checked = el.value === val;
                } else {
                    el.value = val;
                }
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                var saved = JSON.parse(raw);
                var when = saved.__savedAt ? new Date(saved.__savedAt).toLocaleString() : 'earlier';
                var banner = document.createElement('div');
                banner.className = 'mb-4 p-4 rounded-lg border-l-4 border-amber-400 bg-amber-50 dark:bg-amber-900/20';
                banner.innerHTML =
                    '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">' +
                        '<div>' +
                            '<p class="font-semibold text-amber-800 dark:text-amber-200">Unsaved draft found</p>' +
                            '<p class="text-sm text-amber-700 dark:text-amber-300">From ' + when + '. Restore it?</p>' +
                        '</div>' +
                        '<div class="flex gap-2 flex-shrink-0">' +
                            '<button type="button" data-autosave-action="restore" class="btn btn-primary text-sm py-1.5">Restore</button>' +
                            '<button type="button" data-autosave-action="discard" class="btn btn-outline text-sm py-1.5">Discard</button>' +
                        '</div>' +
                    '</div>';
                form.parentNode.insertBefore(banner, form);
                banner.querySelector('[data-autosave-action="restore"]').addEventListener('click', function() {
                    restore(saved);
                    banner.remove();
                });
                banner.querySelector('[data-autosave-action="discard"]').addEventListener('click', function() {
                    try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
                    banner.remove();
                });
            }
        } catch (e) {}

        var saveTimer = null;
        function scheduleSave() {
            isDirty = true;
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function() {
                try { localStorage.setItem(STORAGE_KEY, JSON.stringify(serialize())); } catch (e) {}
            }, 500);
        }
        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', scheduleSave);

        form.addEventListener('submit', function() {
            isDirty = false;
            try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        });

        window.addEventListener('beforeunload', function(e) {
            if (!isDirty) return;
            e.preventDefault();
            e.returnValue = '';
        });
    };
    </script>
    @stack('scripts')
</body>
</html>
