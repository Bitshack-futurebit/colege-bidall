<x-app-layout>
    <x-slot name="title">WhatsApp Broadcast — Agent</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('agent.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">WhatsApp Broadcast</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Build a share-ready WhatsApp message for your community auctions.</p>
                </div>
            </div>

            @if($regions->isEmpty())
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">You're not assigned to any community regions yet.</p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">An admin will assign you once approved.</p>
                    </div>
                </div>
            @else
                <div x-data="agentBlast()" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left: Selectors -->
                    <div class="space-y-6">
                        @if($regions->count() > 1)
                            <div class="card">
                                <div class="p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Select Region</h2>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($regions as $region)
                                            <button type="button"
                                                    @click="selectRegion({{ $region->id }})"
                                                    :class="selectedRegion === {{ $region->id }} ? 'bg-teal-600 text-white border-teal-600' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:border-teal-400'"
                                                    class="px-4 py-2 rounded-lg border text-sm font-medium transition">
                                                {{ $region->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Message Style -->
                        <div class="card" x-show="selectedRegion" x-cloak>
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Message Style</h2>
                                <div class="grid grid-cols-3 gap-3">
                                    <button type="button"
                                            @click="style = 'new'"
                                            :class="style === 'new' ? 'ring-2 ring-teal-500 bg-teal-50 dark:bg-teal-900/30' : 'bg-gray-50 dark:bg-gray-700'"
                                            class="p-4 rounded-lg text-center transition">
                                        <div class="text-2xl mb-1">&#128226;</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Announcement</div>
                                    </button>
                                    <button type="button"
                                            @click="style = 'closing'"
                                            :class="style === 'closing' ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-900/30' : 'bg-gray-50 dark:bg-gray-700'"
                                            class="p-4 rounded-lg text-center transition">
                                        <div class="text-2xl mb-1">&#9203;</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Closing Soon</div>
                                    </button>
                                    <button type="button"
                                            @click="style = 'hot'"
                                            :class="style === 'hot' ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-900/30' : 'bg-gray-50 dark:bg-gray-700'"
                                            class="p-4 rounded-lg text-center transition">
                                        <div class="text-2xl mb-1">&#128293;</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Highlights</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Select Auction -->
                        <div class="card" x-show="selectedRegion" x-cloak>
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Select Auction</h2>
                                <template x-if="regionAuctions.length === 0">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming or live auctions in this region right now.</p>
                                </template>
                                <select x-show="regionAuctions.length > 0" x-model="selectedAuction" @change="onAuctionChange()" class="input">
                                    <option value="">-- Choose an auction --</option>
                                    <template x-for="a in regionAuctions" :key="a.id">
                                        <option :value="a.id" x-text="a.title + ' (' + a.status + ' — ' + a.lots.length + ' lots)'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Select Lots -->
                        <div class="card" x-show="selectedAuction" x-cloak>
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Select Lots</h2>
                                    <div class="flex gap-2">
                                        <button type="button" @click="selectAll()" class="text-sm text-teal-600 hover:underline">Select All</button>
                                        <span class="text-gray-300 dark:text-gray-600">|</span>
                                        <button type="button" @click="selectNone()" class="text-sm text-gray-500 hover:underline">Clear</button>
                                    </div>
                                </div>

                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    <template x-for="lot in currentLots" :key="lot.id">
                                        <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                            <input type="checkbox" :value="String(lot.id)" x-model="selectedLots"
                                                   class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'Lot ' + lot.lot_number"></span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="lot.title"></span>
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    <span x-text="lot.price_label"></span>
                                                    <template x-if="lot.bids > 0">
                                                        <span x-text="' · ' + lot.bids + ' bid' + (lot.bids > 1 ? 's' : '')"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </label>
                                    </template>
                                </div>

                                <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                    <span x-text="selectedLots.length"></span> of <span x-text="currentLots.length"></span> lots selected
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Preview & Send -->
                    <div class="space-y-6">
                        <div class="card lg:sticky lg:top-8">
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Message Preview</h2>

                                <div class="bg-[#e5ddd5] dark:bg-gray-700 rounded-lg p-4 mb-6">
                                    <div class="bg-white dark:bg-gray-600 rounded-lg p-3 shadow-sm max-w-sm ml-auto">
                                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap"
                                           x-text="generatedMessage || 'Pick a region, an auction, and at least one lot to preview your message.'"></p>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center mb-4 text-sm" x-show="generatedMessage" x-cloak>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <span x-text="generatedMessage.length"></span> characters
                                    </span>
                                    <span x-show="selectedLots.length > 10" class="text-amber-600 dark:text-amber-400">
                                        Tip: keep under 10 lots for readability
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <a :href="whatsappUrl" target="_blank"
                                       x-show="generatedMessage" x-cloak
                                       class="btn bg-teal-600 hover:bg-teal-700 text-white w-full flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        Send to WhatsApp
                                    </a>

                                    <button type="button" x-show="generatedMessage" x-cloak @click="copyMessage()" class="btn btn-outline w-full">
                                        <span x-text="copied ? 'Copied!' : 'Copy Message Text'"></span>
                                    </button>
                                </div>

                                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                                    <p>Your referral code <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $referralCode }}</span> is auto-included so anyone who signs up via your link is attributed to you.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function agentBlast() {
            const auctionsData = @json($auctionsJson);
            const regions = @json($regions->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values());
            const brandName = @json(config('branding.name'));
            const agentName = @json($agentName);
            const referralUrl = @json($referralUrl);
            const regionUrls = @json($regionUrls);

            return {
                style: 'new',
                selectedRegion: regions.length === 1 ? regions[0].id : null,
                selectedAuction: '',
                selectedLots: [],
                currentLots: [],
                copied: false,

                get regionAuctions() {
                    if (!this.selectedRegion) return [];
                    return auctionsData.filter(a => a.region_id === this.selectedRegion);
                },

                get activeAuction() {
                    return auctionsData.find(a => a.id == this.selectedAuction);
                },

                get generatedMessage() {
                    const auction = this.activeAuction;
                    if (!auction || this.selectedLots.length === 0) return '';

                    const lots = this.currentLots.filter(l => this.selectedLots.includes(String(l.id)));
                    let msg = '';

                    if (this.style === 'new') {
                        msg += `\u{1F3D8}\u{FE0F} *${auction.region_name} COMMUNITY AUCTION* \u{1F3D8}\u{FE0F}\n\n`;
                        msg += `Hey, *${agentName}* here — your local agent for ${auction.region_name}.\n\n`;
                        msg += `\u{1F4C5} ${auction.status === 'live' ? 'LIVE NOW' : 'Starts: ' + auction.start_time}\n`;
                        msg += `\u{1F4E6} ${lots.length} lot${lots.length > 1 ? 's' : ''} this week\n\n`;
                    } else if (this.style === 'closing') {
                        msg += `\u{23F3} *CLOSING SOON* \u{23F3}\n\n`;
                        msg += `${agentName} here from the *${auction.region_name}* community.\n\n`;
                        if (auction.end_time) {
                            msg += `\u{23F0} Ends: ${auction.end_time}\n\n`;
                        } else if (auction.start_time) {
                            msg += `\u{23F0} ${auction.status === 'live' ? 'Bidding now' : 'Starts'}: ${auction.start_time}\n\n`;
                        }
                        msg += `Last call on these:\n\n`;
                    } else if (this.style === 'hot') {
                        msg += `\u{1F525} *HIGHLIGHTS — ${auction.region_name}* \u{1F525}\n\n`;
                        msg += `${agentName} here. A few standouts in our community auction:\n\n`;
                    }

                    lots.forEach((lot, i) => {
                        const num = String.fromCodePoint(0x31 + i, 0xFE0F, 0x20E3);
                        const emoji = i < 9 ? num + ' ' : `${i + 1}. `;
                        msg += `${emoji}${lot.title}`;
                        msg += ` — ${lot.price_label}`;
                        if (lot.bids > 0 && this.style !== 'new') {
                            msg += ` (${lot.bids} bid${lot.bids > 1 ? 's' : ''})`;
                        }
                        msg += `\n`;
                    });

                    msg += `\nPowered by ${brandName}\n\n`;
                    msg += `\u{1F449} Browse & bid:\n${auction.url}\n\n`;
                    const joinUrl = regionUrls[this.selectedRegion] ?? referralUrl;
                    msg += `\u{1F91D} Want to sell or join the community? Tap here:\n${joinUrl}`;

                    return msg;
                },

                get whatsappUrl() {
                    return 'https://api.whatsapp.com/send?text=' + encodeURIComponent(this.generatedMessage);
                },

                selectRegion(id) {
                    this.selectedRegion = id;
                    this.selectedAuction = '';
                    this.currentLots = [];
                    this.selectedLots = [];
                },

                onAuctionChange() {
                    const auction = this.activeAuction;
                    this.selectedLots = [];
                    this.currentLots = auction ? [...auction.lots] : [];
                },

                selectAll() {
                    this.selectedLots = this.currentLots.map(l => String(l.id));
                },

                selectNone() {
                    this.selectedLots = [];
                },

                copyMessage() {
                    navigator.clipboard.writeText(this.generatedMessage);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
