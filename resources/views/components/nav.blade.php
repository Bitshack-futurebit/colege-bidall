@php
    $wlActive = ($whiteLabel ?? null)?->isActive();
    $wlRedirect = $wlActive ? '?redirect=' . urlencode(url()->current()) : '';
    $loginUrl = route('login') . $wlRedirect;
    $registerUrl = route('register') . $wlRedirect;
@endphp

{{-- ═══════════════════════════════════════════════
     TOP BAR (all screen sizes)
     Mobile: logo + bell + dark mode only
     Desktop: logo + nav links + auth
═══════════════════════════════════════════════ --}}
<nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Logo + desktop nav --}}
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    @if($wlActive)
                    <a href="{{ route('auctioneer.show', $whiteLabel->slug()) }}" class="flex items-center gap-2">
                        @if($whiteLabel->logoUrl())
                            <img src="{{ $whiteLabel->logoUrl() }}" alt="{{ $whiteLabel->businessName() }}" class="h-12 w-12 rounded-full object-cover">
                        @else
                            <div class="h-12 w-12 rounded-full bg-primary-600 flex items-center justify-center text-white text-xl font-bold">{{ substr($whiteLabel->businessName(), 0, 1) }}</div>
                        @endif
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $whiteLabel->businessName() }}</span>
                    </a>
                    @else
                    <a href="/" class="flex items-center gap-2">
                        <img src="/images/gavel-logo.svg" alt="BidAll" class="h-12 w-12">
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ config('branding.name') }}</span>
                    </a>
                    @endif
                </div>

                {{-- Desktop nav links --}}
                <div class="hidden sm:ml-8 sm:flex sm:space-x-6">
                    @if($wlActive)
                    <a href="{{ route('auctioneer.show', $whiteLabel->slug()) }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->is('auctioneer/*') ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:text-primary-600' }}">Home</a>
                    <a href="{{ route('auctions.index', ['auctioneer' => $whiteLabel->slug()]) }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->is('auctions*') ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:text-primary-600' }}">Auctions</a>
                    @else
                    <a href="/" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->is('/') ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:text-primary-600' }}">Home</a>
                    <a href="{{ route('auctioneers.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->is('auctioneers') || request()->is('auctioneer/*') ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:text-primary-600' }}">Auctioneers</a>
                    {{-- Communities nav link PARKED — community subsystem dormant in standalone product --}}
                    @endif
                </div>
            </div>

            {{-- Right side --}}
            <div class="flex items-center space-x-2 sm:space-x-4">
                {{-- Dark mode toggle --}}
                <button @click="darkMode = !darkMode" x-cloak class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg x-show="!darkMode" x-cloak class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                {{-- Notification bell --}}
                @auth
                <button type="button" @click="$store.bell.toggle()" x-init="$store.bell.start()" data-bell class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="$store.bell.count > 0" x-cloak x-text="$store.bell.count" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full"></span>
                </button>
                @endauth

                {{-- Desktop auth --}}
                <div class="hidden sm:flex items-center space-x-4">
                    @auth
                        <div x-data="{ open: false }" @click.away="open = false" x-cloak class="relative">
                            <button @click="open = !open" class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400">
                                <span>{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" @click="open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Admin Dashboard</a>
                                @elseif(auth()->user()->isAuctioneer())
                                    <a href="{{ route('seller.dashboard') }}" @click="open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Auctioneer Dashboard</a>
                                @else
                                    <a href="{{ route('dashboard') }}" @click="open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Dashboard</a>
                                @endif
                                @if(auth()->user()->isAuctioneer())
                                    <a href="{{ route('seller.profile') }}" @click="open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Profile</a>
                                @else
                                    <a href="{{ route('profile.edit') }}" @click="open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Profile</a>
                                @endif
                                <hr class="my-1 border-gray-200 dark:border-gray-700">
                                <form method="POST" action="{{ route('logout') }}" onsubmit="return refreshCsrfAndSubmit(this)">
                                    @csrf
                                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">Logout</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ $loginUrl }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400">Login</a>
                        <a href="{{ $registerUrl }}" class="btn btn-primary btn-sm">Sign Up</a>
                    @endauth
                </div>
            </div>

        </div>
    </div>
</nav>

{{-- ═══════════════════════════════════════════════
     MOBILE BOTTOM NAV (hidden on sm+)
═══════════════════════════════════════════════ --}}
@if(!$wlActive)
<nav class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700"
     style="padding-bottom: env(safe-area-inset-bottom)">
    <div class="grid grid-cols-4 h-16">

        {{-- Home --}}
        <a href="/" class="flex flex-col items-center justify-center gap-0.5 {{ request()->is('/') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] font-medium leading-none">Home</span>
        </a>

        {{-- Auctioneers --}}
        <a href="{{ route('auctioneers.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->is('auctioneers') || request()->is('auctioneer/*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="text-[10px] font-medium leading-none">Auctioneers</span>
        </a>

        {{-- Communities bottom-nav link PARKED — community subsystem dormant in standalone product --}}

        {{-- Account --}}
        @auth
            @php
                $accountActive = request()->is('dashboard*') || request()->is('seller*') || request()->is('admin*');
                $dashboardUrl = auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isAuctioneer() ? route('seller.dashboard') : route('dashboard'));
            @endphp
            <a href="{{ $dashboardUrl }}"
               @click.prevent="$store.mobileMenu.open = true"
               class="flex flex-col items-center justify-center gap-0.5 {{ $accountActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-[10px] font-medium leading-none">Account</span>
            </a>
        @else
            <a href="{{ $loginUrl }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->is('login') || request()->is('register') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <span class="text-[10px] font-medium leading-none">Login</span>
            </a>
        @endauth

    </div>
</nav>

{{-- Mobile account drawer --}}
@auth
<div x-data>
    <div x-show="$store.mobileMenu.open" x-cloak
         class="sm:hidden fixed inset-0 z-50"
         @click="$store.mobileMenu.open = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             style="padding-bottom: calc(env(safe-area-inset-bottom) + 4.5rem)">
            <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3 mb-4"></div>
            <div class="px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ auth()->user()->name }}
            </div>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" @click="$store.mobileMenu.open = false"
                   class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                    Admin Dashboard
                </a>
            @elseif(auth()->user()->isAuctioneer())
                <a href="{{ route('seller.dashboard') }}" @click="$store.mobileMenu.open = false"
                   class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                    Dashboard
                </a>
            @else
                <a href="{{ route('dashboard') }}" @click="$store.mobileMenu.open = false"
                   class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
            @endif
            @if(auth()->user()->isAuctioneer())
                <a href="{{ route('seller.profile') }}" @click="$store.mobileMenu.open = false"
                   class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </a>
            @else
                <a href="{{ route('profile.edit') }}" @click="$store.mobileMenu.open = false"
                   class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </a>
            @endif
            <div class="mx-4 my-2 border-t border-gray-100 dark:border-gray-700"></div>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return refreshCsrfAndSubmit(this)">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-3 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
@endauth
@endif

{{-- ═══════════════════════════════════════════════
     NOTIFICATION DROPDOWN + MODAL
═══════════════════════════════════════════════ --}}
@auth
<div x-data x-init="$store.bell.start()" @click.window="if(!$event.target.closest('[data-bell]')) $store.bell.close()">
    {{-- Dropdown panel --}}
    <div x-show="$store.bell.open" x-cloak x-transition
        data-bell
        class="fixed top-16 right-4 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xl z-[1002]">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button type="button" @click="$store.bell.switchTab('new')" class="flex-1 px-4 py-2.5 text-xs font-semibold transition" :class="$store.bell.tab === 'new' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 dark:text-gray-400'">
                New <span x-show="$store.bell.count > 0" x-text="'(' + $store.bell.count + ')'" class="text-red-500"></span>
            </button>
            <button type="button" @click="$store.bell.switchTab('history')" class="flex-1 px-4 py-2.5 text-xs font-semibold transition" :class="$store.bell.tab === 'history' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 dark:text-gray-400'">
                History
            </button>
        </div>
        <div class="flex items-center justify-end gap-3 px-4 py-1.5" x-show="$store.bell.tab === 'new' && $store.bell.count > 0">
            <button type="button" @click="$store.bell.markAllRead()" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Clear all</button>
        </div>
        <div x-show="$store.bell.tab === 'new'" class="max-h-80 overflow-y-auto">
            <template x-if="$store.bell.notifications.length === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">All caught up!</div>
            </template>
            <template x-for="n in $store.bell.notifications" :key="n.id">
                <div class="flex items-start border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <button type="button" @click="$store.bell.viewNotification(n)" class="flex-1 text-left px-4 py-3">
                        <div class="flex items-start gap-2">
                            <span class="w-2 h-2 mt-1.5 rounded-full shrink-0" :style="'background:' + $store.bell.typeColor(n.type)"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="n.title"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="n.body"></p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1" x-text="n.time"></p>
                            </div>
                        </div>
                    </button>
                    <button type="button" @click="$store.bell.markRead(n.id)" class="shrink-0 p-2 mt-2 mr-2 rounded-lg text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Dismiss">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>
        <div x-show="$store.bell.tab === 'history'" class="max-h-80 overflow-y-auto">
            <template x-if="$store.bell.history.length === 0 && $store.bell.historyLoaded">
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No notifications yet.</div>
            </template>
            <template x-for="n in $store.bell.history" :key="n.id">
                <button type="button" @click="$store.bell.viewNotification(n)" class="w-full text-left px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="n.read ? 'opacity-60' : ''">
                    <div class="flex items-start gap-2">
                        <span class="w-2 h-2 mt-1.5 rounded-full shrink-0" :style="'background:' + $store.bell.typeColor(n.type)"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="n.title"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="n.body"></p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1" x-text="n.date || n.time"></p>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    {{-- Notification view modal --}}
    <div x-show="$store.bell.modal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" @keydown.escape.window="$store.bell.closeModal()">
        <div class="absolute inset-0 bg-black/50" @click="$store.bell.closeModal()"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl overflow-hidden" x-transition>
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" :style="'background:' + $store.bell.typeColor($store.bell.viewing?.type)"></span>
                    <span class="text-xs font-semibold uppercase" :style="'color:' + $store.bell.typeColor($store.bell.viewing?.type)" x-text="$store.bell.viewing?.type"></span>
                </div>
                <button type="button" @click="$store.bell.closeModal()" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-5 py-5">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="$store.bell.viewing?.title"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 leading-relaxed whitespace-pre-line" x-text="$store.bell.viewing?.body"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-4" x-text="$store.bell.viewing?.date || $store.bell.viewing?.time"></p>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button type="button" @click="$store.bell.closeModal()" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Close</button>
                <button type="button" x-show="$store.bell.viewing?.link" @click="$store.bell.goToLink()" class="px-4 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg">Open Link</button>
            </div>
        </div>
    </div>
</div>
@endauth
