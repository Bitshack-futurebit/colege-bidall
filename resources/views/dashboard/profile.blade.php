<x-app-layout>
    <x-slot name="title">My Profile</x-slot>

    @php
        $score = $user->profileScore();
        $idStatus = $user->idStatus();
        $scoreColor = $score >= 100 ? 'teal' : ($score >= 80 ? 'green' : ($score >= 50 ? 'amber' : 'red'));
        $scoreLabel = $score >= 100 ? 'Complete' : ($score >= 80 ? 'Good' : ($score >= 50 ? 'Partial' : 'Incomplete'));
        $items = [
            ['label' => 'Profile name',    'done' => (bool) $user->name,     'pts' => 5],
            ['label' => 'Email',           'done' => (bool) $user->email,    'pts' => 5],
            ['label' => 'WhatsApp number', 'done' => (bool) $user->whatsapp, 'pts' => 25],
            ['label' => 'Street address','done' => (bool) $user->address,   'pts' => 10],
            ['label' => 'City',         'done' => (bool) $user->city,        'pts' => 10],
            ['label' => 'Province',     'done' => (bool) $user->province,    'pts' => 10],
            ['label' => 'Postal code',  'done' => (bool) $user->postal_code, 'pts' => 5],
            ['label' => 'ID document',  'done' => $idStatus !== 'none',
             'pts' => $idStatus === 'verified' ? 30 : ($idStatus === 'pending' ? 15 : 0),
             'max' => 30,
             'note' => $idStatus === 'verified' ? 'Verified' : ($idStatus === 'pending' ? 'Pending review (+15 now, +30 when verified)' : null)],
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">My Profile</h1>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- ── Main form ── --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Personal Information --}}
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Personal Information</h2>

                            <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')

                                <div class="space-y-4">
                                    {{-- Contact --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label for="name" class="label">Name</label>
                                                    <input id="name" type="text" name="name"
                                                           value="{{ old('name', $user->name) }}" required
                                                           class="input @error('name') input-error @enderror">
                                                    @error('name')<p class="error-message">{{ $message }}</p>@enderror
                                                </div>
                                                <div>
                                                    <label for="surname" class="label">Surname</label>
                                                    <input id="surname" type="text" name="surname"
                                                           value="{{ old('surname', $user->surname) }}"
                                                           class="input @error('surname') input-error @enderror">
                                                    @error('surname')<p class="error-message">{{ $message }}</p>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="email" class="label">Email</label>
                                            <input id="email" type="email" name="email"
                                                   value="{{ old('email', $user->email) }}" required
                                                   class="input @error('email') input-error @enderror">
                                            @error('email')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="whatsapp" class="label">WhatsApp Number</label>
                                            <input id="whatsapp" type="tel" name="whatsapp"
                                                   value="{{ old('whatsapp', $user->whatsapp) }}"
                                                   placeholder="+27 xx xxx xxxx"
                                                   class="input @error('whatsapp') input-error @enderror">
                                            @error('whatsapp')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    {{-- Address --}}
                                    <div>
                                        <label for="address" class="label">Street Address</label>
                                        <input id="address" type="text" name="address"
                                               value="{{ old('address', $user->address) }}"
                                               class="input @error('address') input-error @enderror">
                                        @error('address')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <label for="city" class="label">City / Town</label>
                                            <input id="city" type="text" name="city"
                                                   value="{{ old('city', $user->city) }}"
                                                   class="input @error('city') input-error @enderror">
                                            @error('city')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="province" class="label">Province</label>
                                            <select id="province" name="province" class="input @error('province') input-error @enderror">
                                                <option value="">— select —</option>
                                                @foreach(['Eastern Cape','Free State','Gauteng','KwaZulu-Natal','Limpopo','Mpumalanga','Northern Cape','North West','Western Cape'] as $p)
                                                    <option value="{{ $p }}" {{ old('province', $user->province) === $p ? 'selected' : '' }}>{{ $p }}</option>
                                                @endforeach
                                            </select>
                                            @error('province')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="postal_code" class="label">Postal Code</label>
                                            <input id="postal_code" type="text" name="postal_code"
                                                   value="{{ old('postal_code', $user->postal_code) }}"
                                                   maxlength="10"
                                                   class="input @error('postal_code') input-error @enderror">
                                            @error('postal_code')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    {{-- Notification preferences --}}
                                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Notification Preferences</div>
                                        <div class="space-y-3">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="hidden" name="email_notifications" value="0">
                                                <input type="checkbox" name="email_notifications" value="1"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                                       {{ old('email_notifications', $user->email_notifications) ? 'checked' : '' }}>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Email notifications</div>
                                                    <div class="text-xs text-gray-500">Win confirmations and important account updates</div>
                                                </div>
                                            </label>
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="hidden" name="event_reminders" value="0">
                                                <input type="checkbox" name="event_reminders" value="1"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                                       {{ old('event_reminders', $user->event_reminders) ? 'checked' : '' }}>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Auction reminders</div>
                                                    <div class="text-xs text-gray-500">Reminder before auctions you've registered for go live</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- ID Document --}}
                                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-2 mb-1">
                                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Identity Verification</div>
                                            @if($idStatus === 'verified')
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded-full">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Verified
                                                </span>
                                            @elseif($idStatus === 'pending')
                                                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-full">Pending review</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                            Upload a clear photo or scan of your South African ID, passport, or driver's licence.
                                            Your document is stored securely and only reviewed by BidAll staff to reduce fraud.
                                            Worth <strong>+30 points</strong> once verified.
                                        </p>

                                        @if($idStatus !== 'none')
                                            <div class="flex items-center gap-3 mb-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <svg class="w-8 h-8 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $idStatus === 'verified' ? 'Verified document on file' : 'Document uploaded — awaiting review' }}
                                                    </div>
                                                    @if($idStatus === 'verified')
                                                        <div class="text-xs text-green-600 dark:text-green-400">Verified {{ $user->id_verified_at->format('d M Y') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500 mb-2">Upload a new document to replace the current one (resets verification):</p>
                                        @endif

                                        <input type="file" name="id_document" id="id_document"
                                               accept=".jpg,.jpeg,.png,.pdf"
                                               class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                      file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                                                      file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700
                                                      dark:file:bg-primary-900/30 dark:file:text-primary-400
                                                      hover:file:bg-primary-100 dark:hover:file:bg-primary-900/50">
                                        <p class="text-xs text-gray-400 mt-1">JPG, PNG or PDF · max 10 MB</p>
                                        @error('id_document')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Change Password --}}
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Change Password</h2>

                            <form method="POST" action="{{ route('dashboard.profile.update') }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="name" value="{{ $user->name }}">
                                <input type="hidden" name="surname" value="{{ $user->surname }}">
                                <input type="hidden" name="email" value="{{ $user->email }}">

                                <div class="space-y-4">
                                    <div x-data="{ show: false }">
                                        <label for="current_password" class="label">Current Password</label>
                                        <div class="relative">
                                            <input id="current_password" :type="show ? 'text' : 'password'" name="current_password"
                                                   class="input pr-10 @error('current_password') input-error @enderror">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                        @error('current_password')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div x-data="{ show: false }">
                                        <label for="password" class="label">New Password</label>
                                        <div class="relative">
                                            <input id="password" :type="show ? 'text' : 'password'" name="password"
                                                   class="input pr-10 @error('password') input-error @enderror">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                        @error('password')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div x-data="{ show: false }">
                                        <label for="password_confirmation" class="label">Confirm New Password</label>
                                        <div class="relative">
                                            <input id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation" class="input pr-10">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── Sidebar ── --}}
                <div class="lg:col-span-1 space-y-6">

                    {{-- Profile Score Card --}}
                    <div class="card p-6">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Profile Score</h3>

                        {{-- Circle --}}
                        <div class="flex flex-col items-center mb-5">
                            <div class="relative w-28 h-28">
                                <svg class="w-28 h-28 -rotate-90" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="currentColor"
                                            class="text-gray-200 dark:text-gray-700" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="currentColor"
                                            class="transition-all duration-700
                                                   {{ $scoreColor === 'teal' ? 'text-teal-500' : ($scoreColor === 'green' ? 'text-green-500' : ($scoreColor === 'amber' ? 'text-amber-500' : 'text-red-500')) }}"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            stroke-dasharray="{{ $score }} {{ 100 - $score }}"/>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $score }}</span>
                                    <span class="text-xs text-gray-500">/100</span>
                                </div>
                            </div>
                            <span class="mt-2 text-sm font-semibold
                                {{ $scoreColor === 'teal' ? 'text-teal-600 dark:text-teal-400' : ($scoreColor === 'green' ? 'text-green-600 dark:text-green-400' : ($scoreColor === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400')) }}">
                                {{ $scoreLabel }}
                            </span>
                        </div>

                        {{-- Checklist --}}
                        <ul class="space-y-2">
                            @foreach($items as $item)
                                <li class="flex items-center justify-between text-sm gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        @if($item['done'])
                                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                                        @endif
                                        <span class="{{ $item['done'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500' }} truncate">
                                            {{ $item['label'] }}
                                            @if(isset($item['note']))
                                                <span class="text-xs font-normal text-amber-600 dark:text-amber-400 block leading-tight">{{ $item['note'] }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold {{ $item['done'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                        +{{ isset($item['max']) ? $item['max'] : $item['pts'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        @if($score < 100)
                            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 text-center">
                                Complete your profile to build trust with auctioneers and other users.
                            </p>
                        @endif
                    </div>

                    {{-- Account Stats --}}
                    <div class="card p-6">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Account</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Paddle Number</div>
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">#{{ $user->paddle_number }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Your anonymous bidding ID</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Member Since</div>
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->created_at->format('M d, Y') }}</div>
                            </div>
                            <div class="grid grid-cols-3 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-center">
                                    <div class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['total_bids'] }}</div>
                                    <div class="text-xs text-gray-500">Total bids</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['lots_won'] }}</div>
                                    <div class="text-xs text-gray-500">Won</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['watchlist_count'] }}</div>
                                    <div class="text-xs text-gray-500">Watchlist</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(config('regional.whatsapp.support_group_url'))
                    <div class="card bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 p-6">
                        <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Need Help?</h3>
                        <p class="text-xs text-yellow-800 dark:text-yellow-200 mb-4">Contact us if you have any questions about your account.</p>
                        <a href="{{ config('regional.whatsapp.support_group_url') }}" target="_blank"
                           class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-4 py-2 rounded-lg font-semibold transition-colors w-full text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            Contact Support
                        </a>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
