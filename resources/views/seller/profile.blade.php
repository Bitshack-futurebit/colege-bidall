<x-app-layout>
    <x-slot name="title">Auctioneer Profile</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Auctioneer Profile</h1>
                </div>
                <a href="{{ route('auctioneer.show', $auctioneer->slug) }}" target="_blank" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Public Profile
                </a>
            </div>

            <div class="card mb-6">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Business Information</h2>

                    <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data" id="profileForm" onsubmit="return geocodeProfileBeforeSubmit(event)">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-6">
                            <!-- Banner Image -->
                            <div>
                                <label class="label">Banner Image (Cover Photo)</label>
                                <div class="space-y-4">
                                    <div id="banner_preview_wrap">
                                        @if($auctioneer->banner_image)
                                            <img id="banner_preview"
                                                 src="{{ Storage::disk('public')->url($auctioneer->banner_image) }}"
                                                 alt="Banner"
                                                 class="w-full h-48 rounded-lg object-cover">
                                        @else
                                            <div id="banner_placeholder" class="w-full h-48 bg-gradient-to-r from-primary-400 to-primary-600 rounded-lg flex items-center justify-center">
                                                <div class="text-center text-white">
                                                    <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <p class="text-sm opacity-75">No banner image</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <input type="file" name="banner_image" id="banner_image" accept="image/*" class="input"
                                               onchange="previewImage(this, 'banner_preview_wrap', 'w-full h-48 rounded-lg object-cover', false)">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Recommended: Wide image, 1920x400px or similar aspect ratio</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Logo -->
                            <div>
                                <label class="label">Business Logo</label>
                                <div class="space-y-4">
                                    <div id="logo_preview_wrap" class="flex justify-center">
                                        @if($auctioneer->logo)
                                            <img id="logo_preview"
                                                 src="{{ Storage::disk('public')->url($auctioneer->logo) }}"
                                                 alt="Logo"
                                                 class="w-32 h-32 rounded-lg object-cover">
                                        @else
                                            <div id="logo_placeholder" class="w-32 h-32 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <input type="file" name="logo" id="logo" accept="image/*" class="input"
                                               onchange="previewImage(this, 'logo_preview_wrap', 'w-32 h-32 rounded-lg object-cover', false)">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Square image, 200x200px+</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="business_name" class="label">Business Name *</label>
                                    <input id="business_name"
                                           type="text"
                                           name="business_name"
                                           value="{{ old('business_name', $auctioneer->business_name) }}"
                                           required
                                           class="input @error('business_name') input-error @enderror">
                                    @error('business_name')<p class="error-message">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="phone" class="label">Phone *</label>
                                    <input id="phone"
                                           type="text"
                                           name="phone"
                                           value="{{ old('phone', $user->phone) }}"
                                           required
                                           class="input @error('phone') input-error @enderror">
                                    @error('phone')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="whatsapp_number" class="label">WhatsApp Number *</label>
                                <input id="whatsapp_number"
                                       type="text"
                                       name="whatsapp_number"
                                       value="{{ old('whatsapp_number', $auctioneer->whatsapp_number) }}"
                                       required
                                       class="input @error('whatsapp_number') input-error @enderror">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Used for buyer communication</p>
                                @error('whatsapp_number')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="bio" class="label">Bio</label>
                                <textarea id="bio"
                                          name="bio"
                                          rows="4"
                                          class="input @error('bio') input-error @enderror"
                                          placeholder="Tell buyers about your business...">{{ old('bio', $auctioneer->bio) }}</textarea>
                                @error('bio')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="rules" class="label">Auctioneer Rules</label>
                                <textarea id="rules"
                                          name="rules"
                                          rows="12"
                                          class="input @error('rules') input-error @enderror font-mono text-sm"
                                          placeholder="Auction rules and terms...">{{ old('rules', $auctioneer->rules ?? \App\Models\Auctioneer::DEFAULT_RULES) }}</textarea>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    These rules will be displayed to bidders on your public profile. Default terms provided - customize as needed.
                                </p>
                                @error('rules')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="address" class="label">Address</label>
                                <input id="address"
                                       type="text"
                                       name="address"
                                       value="{{ old('address', $user->address) }}"
                                       class="input">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="city" class="label">City</label>
                                    <input id="city"
                                           type="text"
                                           name="city"
                                           value="{{ old('city', $user->city) }}"
                                           class="input">
                                </div>

                                <div>
                                    <label for="province" class="label">Province</label>
                                    <input id="province"
                                           type="text"
                                           name="province"
                                           value="{{ old('province', $user->province) }}"
                                           class="input">
                                </div>
                            </div>

                            @if(config('regional.features.map_discovery'))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="lat" class="label">Latitude (optional)</label>
                                        <input id="lat"
                                               type="text"
                                               name="lat"
                                               value="{{ old('lat', $user->lat) }}"
                                               placeholder="-30.8298"
                                               class="input">
                                    </div>

                                    <div>
                                        <label for="lng" class="label">Longitude (optional)</label>
                                        <input id="lng"
                                               type="text"
                                               name="lng"
                                               value="{{ old('lng', $user->lng) }}"
                                               placeholder="30.3943"
                                               class="input">
                                    </div>
                                </div>

                                <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                        <strong>Map Location:</strong> Your business will appear on our discovery map.<br>
                                        Coordinates are auto-detected from your address when you save. To override, enter manually:<br>
                                        <strong>Find your coordinates:</strong> Open <a href="https://www.google.com/maps" target="_blank" class="underline">Google Maps</a>, right-click your location, and copy the coordinates (first number is Latitude, second is Longitude).
                                    </p>
                                </div>
                            @endif

                            <!-- Social Media & WhatsApp -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Social Media & Contact Links</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Add your social media profiles to help bidders connect with you
                                </p>

                                <div class="space-y-4">
                                    <!-- WhatsApp Group Link -->
                                    <div>
                                        <label for="whatsapp_group_link" class="label">
                                            <svg class="w-5 h-5 inline text-green-600 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                            </svg>
                                            WhatsApp Group Link
                                        </label>
                                        <input id="whatsapp_group_link"
                                               type="url"
                                               name="whatsapp_group_link"
                                               value="{{ old('whatsapp_group_link', $auctioneer->whatsapp_group_link) }}"
                                               placeholder="https://chat.whatsapp.com/..."
                                               class="input @error('whatsapp_group_link') input-error @enderror">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            Share your WhatsApp group invite link so followers can join your community
                                        </p>
                                        @error('whatsapp_group_link')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Facebook -->
                                        <div>
                                            <label for="facebook" class="label">
                                                <svg class="w-5 h-5 inline text-blue-600 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                                Facebook
                                            </label>
                                            <input id="facebook"
                                                   type="url"
                                                   name="facebook"
                                                   value="{{ old('facebook', $auctioneer->facebook) }}"
                                                   placeholder="https://facebook.com/yourpage"
                                                   class="input @error('facebook') input-error @enderror">
                                            @error('facebook')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>

                                        <!-- Instagram -->
                                        <div>
                                            <label for="instagram" class="label">
                                                <svg class="w-5 h-5 inline text-pink-600 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                                </svg>
                                                Instagram
                                            </label>
                                            <input id="instagram"
                                                   type="url"
                                                   name="instagram"
                                                   value="{{ old('instagram', $auctioneer->instagram) }}"
                                                   placeholder="https://instagram.com/yourprofile"
                                                   class="input @error('instagram') input-error @enderror">
                                            @error('instagram')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>

                                        <!-- TikTok -->
                                        <div>
                                            <label for="tiktok" class="label">
                                                <svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                                </svg>
                                                TikTok
                                            </label>
                                            <input id="tiktok"
                                                   type="url"
                                                   name="tiktok"
                                                   value="{{ old('tiktok', $auctioneer->tiktok) }}"
                                                   placeholder="https://tiktok.com/@yourprofile"
                                                   class="input @error('tiktok') input-error @enderror">
                                            @error('tiktok')<p class="error-message">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- White-Label Branding -->
            <div class="card mb-6">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">White-Label Branding</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Customise your public profile with your own brand colours and identity. When enabled, visitors to your profile and auctions will see your branding instead of {{ config('branding.name') }}.
                    </p>

                    <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_section" value="white_label">

                        <div class="space-y-6">
                            <!-- Enable Toggle -->
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="white_label_enabled" value="0">
                                    <input type="checkbox" name="white_label_enabled" value="1" class="sr-only peer" {{ old('white_label_enabled', $auctioneer->white_label_enabled) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-600 peer-checked:bg-primary-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Enable White-Label Branding</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Primary Brand Color -->
                                <div>
                                    <label class="label">Primary Brand Colour</label>
                                    <div class="flex items-center gap-3">
                                        <input type="color" name="brand_primary_color" value="{{ old('brand_primary_color', $auctioneer->brand_primary_color ?? '#22c55e') }}" class="h-10 w-14 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                                        <input type="text" value="{{ old('brand_primary_color', $auctioneer->brand_primary_color ?? '#22c55e') }}" class="input flex-1" placeholder="#22c55e" oninput="this.previousElementSibling.value = this.value" pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used for buttons, links, and accents across your branded pages</p>
                                    @error('brand_primary_color')<p class="error-message">{{ $message }}</p>@enderror
                                </div>

                                <!-- Secondary Brand Color -->
                                <div>
                                    <label class="label">Secondary Brand Colour (optional)</label>
                                    <div class="flex items-center gap-3">
                                        <input type="color" name="brand_secondary_color" value="{{ old('brand_secondary_color', $auctioneer->brand_secondary_color ?? '#ef4444') }}" class="h-10 w-14 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                                        <input type="text" value="{{ old('brand_secondary_color', $auctioneer->brand_secondary_color ?? '#ef4444') }}" class="input flex-1" placeholder="#ef4444" oninput="this.previousElementSibling.value = this.value" pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used for highlights and call-to-action elements</p>
                                    @error('brand_secondary_color')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Brand Favicon -->
                            <div>
                                <label class="label">Brand Favicon</label>
                                @if($auctioneer->brand_favicon)
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="{{ Storage::url($auctioneer->brand_favicon) }}" alt="Current favicon" class="h-8 w-8 rounded">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Current favicon</span>
                                    </div>
                                @endif
                                <input type="file" name="brand_favicon" accept="image/*" class="input">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Small icon shown in the browser tab. Recommended: 256x256px, max 1MB</p>
                                @error('brand_favicon')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <!-- Hero Text -->
                            <div>
                                <label class="label">Banner Hero Text</label>
                                <textarea name="brand_hero_text" rows="2" maxlength="500" class="input" placeholder="Your tagline or welcome message...">{{ old('brand_hero_text', $auctioneer->brand_hero_text) }}</textarea>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Displayed over your banner image on your public profile. Max 500 characters.</p>
                                @error('brand_hero_text')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Save Branding
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PayFast Integration -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">PayFast Integration</h2>

                    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            Connect your PayFast merchant account to allow winning bidders to pay you directly online.
                            You need a <strong>PayFast Business account</strong> to use this feature.
                            When enabled on an auction, buyers will see a "Pay Now" button after winning.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('seller.profile.update.payfast') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Merchant ID</label>
                                    <input type="text"
                                           name="payfast_merchant_id"
                                           value="{{ old('payfast_merchant_id', $auctioneer->payfast_merchant_id) }}"
                                           placeholder="e.g. 10000100"
                                           class="input @error('payfast_merchant_id') input-error @enderror">
                                    @error('payfast_merchant_id')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label">Merchant Key</label>
                                    <input type="text"
                                           name="payfast_merchant_key"
                                           value="{{ old('payfast_merchant_key', $auctioneer->payfast_merchant_key) }}"
                                           placeholder="e.g. 46f0cd694581a"
                                           class="input @error('payfast_merchant_key') input-error @enderror">
                                    @error('payfast_merchant_key')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label class="label">Passphrase</label>
                                <x-password-input
                                    name="payfast_passphrase"
                                    autocomplete="off"
                                    placeholder="{{ $auctioneer->payfast_passphrase ? '••••••••••• (saved — leave blank to keep current)' : 'Enter your PayFast passphrase' }}" />
                                @error('payfast_passphrase')<p class="error-message">{{ $message }}</p>@enderror
                                <p class="text-xs text-gray-500 mt-1">Your passphrase is stored encrypted. Leave blank to keep the current one.</p>
                            </div>

                            <div>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="payfast_sandbox" value="1"
                                           {{ old('payfast_sandbox', $auctioneer->payfast_sandbox) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Sandbox mode (for testing only)</span>
                                </label>
                            </div>

                            @if($auctioneer->hasPayfastConfigured())
                                <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    PayFast connected — you can enable online payment on your auctions.
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary">
                                Save PayFast Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Account Settings -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Account Settings</h2>

                    <form method="POST" action="{{ route('seller.profile.update.account') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="label">Your Name *</label>
                                <input id="name"
                                       type="text"
                                       name="name"
                                       value="{{ old('name', $user->name) }}"
                                       required
                                       class="input @error('name') input-error @enderror">
                                @error('name')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="label">Email *</label>
                                <input id="email"
                                       type="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="input @error('email') input-error @enderror">
                                @error('email')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Change Password</h3>

                                <div class="space-y-4">
                                    <div x-data="{ show: false }">
                                        <label for="current_password" class="label">Current Password</label>
                                        <div class="relative">
                                            <input id="current_password"
                                                   :type="show ? 'text' : 'password'"
                                                   name="current_password"
                                                   class="input pr-10 @error('current_password') input-error @enderror">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                        @error('current_password')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div x-data="{ show: false }">
                                        <label for="password" class="label">New Password</label>
                                        <div class="relative">
                                            <input id="password"
                                                   :type="show ? 'text' : 'password'"
                                                   name="password"
                                                   class="input pr-10 @error('password') input-error @enderror">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                        @error('password')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div x-data="{ show: false }">
                                        <label for="password_confirmation" class="label">Confirm New Password</label>
                                        <div class="relative">
                                            <input id="password_confirmation"
                                                   :type="show ? 'text' : 'password'"
                                                   name="password_confirmation"
                                                   class="input pr-10">
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    async function geocodeProfileBeforeSubmit(event) {
        var latField = document.getElementById('lat');
        var lngField = document.getElementById('lng');

        // If manual coordinates already entered, submit normally
        if (latField && lngField && latField.value && lngField.value) return true;

        var city = document.getElementById('city') ? document.getElementById('city').value : '';
        if (!city) return true; // No location to geocode

        event.preventDefault();

        try {
            var address = document.getElementById('address') ? document.getElementById('address').value : '';
            var province = document.getElementById('province') ? document.getElementById('province').value : '';

            var coords = await geocodeAddress(address, city, province);
            if (coords && latField && lngField) {
                latField.value = coords.lat;
                lngField.value = coords.lng;
            }
        } catch (e) {
            // Geocoding failed — submit without coordinates
        }

        document.getElementById('profileForm').submit();
        return false;
    }

    async function geocodeAddress(address, city, province) {
        var fullAddress = encodeURIComponent(address + ', ' + city + ', ' + province + ', South Africa');
        var url = 'https://nominatim.openstreetmap.org/search?q=' + fullAddress + '&format=json&limit=1&countrycodes=za';

        var response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        var data = await response.json();

        if (data.length && data[0].lat && data[0].lon) {
            return { lat: data[0].lat, lng: data[0].lon };
        }

        // Fallback: city + province only
        var cityAddress = encodeURIComponent(city + ', ' + province + ', South Africa');
        var cityUrl = 'https://nominatim.openstreetmap.org/search?q=' + cityAddress + '&format=json&limit=1&countrycodes=za';

        response = await fetch(cityUrl, { headers: { 'Accept': 'application/json' } });
        data = await response.json();

        if (data.length && data[0].lat && data[0].lon) {
            return { lat: data[0].lat, lng: data[0].lon };
        }

        return null;
    }

    function previewImage(input, wrapId, classes, isRound) {
        if (!input.files || !input.files[0]) return;

        var file = input.files[0];
        var maxBytes = 8 * 1024 * 1024; // 8MB

        if (file.size > maxBytes) {
            alert('Image is too large (' + (file.size / 1024 / 1024).toFixed(1) + 'MB). Please use an image under 8MB.');
            input.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var wrap = document.getElementById(wrapId);
            wrap.innerHTML = '<img src="' + e.target.result + '" class="' + classes + '">';
        };
        reader.readAsDataURL(input.files[0]);
    }
    </script>
    @endpush
</x-app-layout>
