import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

Alpine.plugin(persist);

// Dark Mode Store
Alpine.store('darkMode', {
    on: Alpine.$persist(true).as('darkMode'),

    toggle() {
        this.on = !this.on;
        this.updateTheme();
    },

    updateTheme() {
        if (this.on) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },

    init() {
        this.updateTheme();
    }
});

// Shared time formatter (used by bidding + countdown components)
function formatTimeMs(ms) {
    const seconds = Math.floor((ms / 1000) % 60);
    const minutes = Math.floor((ms / (1000 * 60)) % 60);
    const hours = Math.floor((ms / (1000 * 60 * 60)) % 24);
    const days = Math.floor(ms / (1000 * 60 * 60 * 24));

    if (days > 0) return `${days}d ${hours}h ${minutes}m`;
    if (hours > 0) return `${hours}h ${minutes}m ${seconds}s`;
    if (minutes > 0) return `${minutes}m ${seconds}s`;
    return `${seconds}s`;
}

// Get current user ID from meta tag (0 if guest)
const _userId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);

// Bidding Component
Alpine.data('bidding', (lotId, currentBid, increment, endTime, totalBids = 0, hasTopBid = false, initialProxyMax = null, proxyEnabled = false, hasReserve = false, reserveMet = false) => ({
    lotId: lotId,
    currentBid: currentBid,
    increment: increment,
    endTime: endTime,
    totalBids: totalBids,
    customAmount: '',
    timeRemaining: 0,
    isUrgent: false,
    hasTopBid: hasTopBid,
    pollingInterval: null,
    showEndedModal: false,
    hasEnded: false,
    wasRunning: false,
    // Proxy bidding state
    proxyMax: initialProxyMax,
    proxyAmount: '',
    bidMode: 'manual',
    proxyEnabled: proxyEnabled,
    proxyLoading: false,
    // UX enhancement state
    _prevHasTopBid: hasTopBid,
    bidFeedback: '',
    bidFeedbackType: '',
    showBidFeedback: false,
    hasReserve: hasReserve,
    reserveMet: reserveMet,
    showReserveCelebration: false,

    get minimumBid() {
        return this.totalBids > 0
            ? parseFloat(this.currentBid) + parseFloat(this.increment)
            : parseFloat(this.currentBid);
    },

    // Timer urgency levels: 'normal' >10min, 'warning' <10min, 'urgent' <2min, 'critical' <30sec
    get urgencyLevel() {
        if (this.timeRemaining <= 0) return 'ended';
        if (this.timeRemaining < 30000) return 'critical';
        if (this.timeRemaining < 120000) return 'urgent';
        if (this.timeRemaining < 600000) return 'warning';
        return 'normal';
    },

    get timerColorClass() {
        switch (this.urgencyLevel) {
            case 'critical': return 'bg-red-200 dark:bg-red-800/60 animate-pulse';
            case 'urgent': return 'bg-red-100 dark:bg-red-900/40';
            case 'warning': return 'bg-amber-100 dark:bg-amber-900/40';
            default: return 'bg-gray-100 dark:bg-gray-700';
        }
    },

    get timerTextClass() {
        switch (this.urgencyLevel) {
            case 'critical': return 'text-red-700 dark:text-red-300';
            case 'urgent': return 'text-red-700 dark:text-red-300';
            case 'warning': return 'text-amber-700 dark:text-amber-300';
            default: return 'text-gray-900 dark:text-gray-100';
        }
    },

    get timerLabelClass() {
        switch (this.urgencyLevel) {
            case 'critical': return 'text-red-600 dark:text-red-400';
            case 'urgent': return 'text-red-600 dark:text-red-400';
            case 'warning': return 'text-amber-600 dark:text-amber-400';
            default: return 'text-gray-500 dark:text-gray-400';
        }
    },

    // "Going once... Going twice..." text
    get goingText() {
        if (this.timeRemaining <= 0) return '';
        if (this.timeRemaining < 30000) return 'Going twice...';
        if (this.timeRemaining < 60000) return 'Going once...';
        return '';
    },

    // Bid gap nudge: "Only R50 to take the lead"
    get bidGapText() {
        if (this.hasTopBid || this.totalBids === 0) return '';
        return 'Only ' + this.formatCurrency(this.increment) + ' to take the lead';
    },

    init() {
        this.customAmount = this.minimumBid;
        this._prevHasTopBid = this.hasTopBid;
        this.updateCountdown();
        this.startPolling();

        // Update countdown every second
        this._countdownTimer = setInterval(() => {
            this.updateCountdown();
        }, 1000);

        // Re-sync immediately when tab becomes visible (mobile browsers throttle background timers)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateCountdown();
            }
        });
    },

    updateCountdown() {
        const now = new Date().getTime();
        const end = new Date(this.endTime).getTime();
        const newTimeRemaining = Math.max(0, end - now);

        // Detect when lot just ended
        if (this.wasRunning && this.timeRemaining > 0 && newTimeRemaining === 0) {
            this.onLotEnded();
        }

        this.timeRemaining = newTimeRemaining;
        this.isUrgent = this.timeRemaining < 300000; // Less than 5 minutes
        this.wasRunning = this.timeRemaining > 0;
    },

    // Show bid feedback banner
    flashFeedback(message, type = 'success') {
        this.bidFeedback = message;
        this.bidFeedbackType = type;
        this.showBidFeedback = true;
        setTimeout(() => { this.showBidFeedback = false; }, 5000);
    },

    onLotEnded() {
        this.hasEnded = true;
        this.showEndedModal = true;

        // Auto-dismiss after 8 seconds
        setTimeout(() => {
            this.showEndedModal = false;
        }, 8000);

        // Stop polling when lot ends
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    },

    closeEndedModal() {
        this.showEndedModal = false;
    },

    get endedModalTitle() {
        if (this.totalBids === 0) {
            return 'No Bids Placed';
        }
        return this.hasTopBid ? 'YOU WON!' : 'BIDDING CLOSED';
    },

    get endedModalMessage() {
        if (this.totalBids === 0) {
            return 'This lot received no bids and was not sold.';
        }
        if (this.hasTopBid) {
            return 'Congratulations! You are the winning bidder.';
        }
        return 'You were outbid. Better luck next time!';
    },

    get endedModalColor() {
        if (this.totalBids === 0) {
            return 'gray';
        }
        return this.hasTopBid ? 'green' : 'red';
    },

    formatTime(ms) { return formatTimeMs(ms); },

    formatCurrency(amount) {
        return 'R' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    async placeBid(amount = null) {
        const bidAmount = (amount !== null && !isNaN(amount) && amount > 0)
            ? amount
            : this.minimumBid;

        if (!bidAmount || isNaN(bidAmount) || bidAmount <= 0) {
            alert('Please enter a valid bid amount.');
            return;
        }

        // Capture $el before await — loses Alpine context after async
        const el = this.$el;

        try {
            const response = await fetch(`/api/lots/${this.lotId}/bid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ amount: bidAmount })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const prevReserveMet = this.reserveMet;
                this.currentBid = data.newBid;
                this.endTime = data.newEndTime || this.endTime;
                this.totalBids = data.totalBids || this.totalBids;
                this.hasTopBid = data.hasTopBid;
                this.customAmount = this.minimumBid;
                if (data.reserveMet !== undefined) this.reserveMet = data.reserveMet;

                // Bid confirmation feedback
                if (this.hasTopBid) {
                    this.flashFeedback('Your bid of ' + this.formatCurrency(data.newBid) + " is confirmed — you're winning!", 'success');
                }

                // Reserve met celebration
                if (this.hasReserve && !prevReserveMet && this.reserveMet) {
                    this.showReserveCelebration = true;
                    setTimeout(() => { this.showReserveCelebration = false; }, 4000);
                }

                // Flash animation
                if (el) {
                    el.classList.add('animate-bid-flash');
                    setTimeout(() => el.classList.remove('animate-bid-flash'), 500);
                }
            } else {
                this.flashFeedback(data.message || 'Failed to place bid', 'error');
            }
        } catch (error) {
            console.error('Bid error:', error);
            this.flashFeedback('Failed to place bid. Please try again.', 'error');
        }
    },

    async setProxy(maxAmount) {
        if (!maxAmount || isNaN(maxAmount) || maxAmount <= 0) {
            alert('Please enter a valid maximum bid amount.');
            return;
        }

        this.proxyLoading = true;

        try {
            const response = await fetch(`/api/lots/${this.lotId}/proxy`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ max_amount: maxAmount })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.proxyMax = parseFloat(data.proxyMax);
                this.currentBid = data.currentBid;
                this.totalBids = data.totalBids || this.totalBids;
                this.hasTopBid = data.hasTopBid;
                this.endTime = data.newEndTime || this.endTime;
                this.customAmount = this.minimumBid;
                this.proxyAmount = '';
            } else {
                alert(data.message || 'Failed to set proxy bid.');
            }
        } catch (error) {
            console.error('Proxy bid error:', error);
            alert('Failed to set proxy bid. Please try again.');
        } finally {
            this.proxyLoading = false;
        }
    },

    async cancelProxy() {
        this.proxyLoading = true;

        try {
            const response = await fetch(`/api/lots/${this.lotId}/proxy`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.proxyMax = null;
                this.proxyAmount = '';
            } else {
                alert(data.message || 'Failed to cancel proxy bid.');
            }
        } catch (error) {
            console.error('Cancel proxy error:', error);
            alert('Failed to cancel proxy bid. Please try again.');
        } finally {
            this.proxyLoading = false;
        }
    },

    _doPoll() {
        return fetch(`/api/lots/${this.lotId}/status`)
            .then(response => {
                if (!response.ok) return;
                return response.json();
            })
            .then(data => {
                if (!data) return;
                const prevReserveMet = this.reserveMet;

                if (data.currentBid !== this.currentBid) {
                    this._prevHasTopBid = this.hasTopBid;
                    this.currentBid = data.currentBid;
                    this.totalBids = data.totalBids || this.totalBids;
                    this.hasTopBid = _userId > 0 && data.winningBidderId === _userId;
                    this.customAmount = this.minimumBid;

                    // Outbid detection — only when user was previously winning
                    if (this._prevHasTopBid && !this.hasTopBid && _userId > 0) {
                        this.flashFeedback("You've been outbid! Bid " + this.formatCurrency(this.minimumBid) + ' to retake the lead.', 'outbid');
                    }
                }
                if (data.endTime !== this.endTime) {
                    this.endTime = data.endTime;
                }
                if (data.reserveMet !== undefined) {
                    this.reserveMet = data.reserveMet;
                    // Reserve met celebration from another bidder's bid
                    if (this.hasReserve && !prevReserveMet && this.reserveMet) {
                        this.showReserveCelebration = true;
                        setTimeout(() => { this.showReserveCelebration = false; }, 4000);
                    }
                }
            })
            .catch(() => {});
    },

    _startInterval() {
        if (!this.pollingInterval) {
            this.pollingInterval = setInterval(() => this._doPoll(), 5000);
        }
    },

    async startPolling() {
        this._startInterval();

        // Pause polling when tab is hidden — biggest server load saver
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            } else {
                this._doPoll();
                this._startInterval();
            }
        });
    },

    destroy() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    }
}));

// Synthesized sound effects for Live auctions (WebAudio — no asset downloads).
// Exposed on window.liveSfx so Alpine components can call it.
window.liveSfx = (() => {
    let ctx = null;
    let tickTimer = null;
    let enabled = localStorage.getItem('liveSfxEnabled') !== '0';

    const ensureCtx = () => {
        if (!enabled) return null;
        if (!ctx) {
            try { ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { return null; }
        }
        if (ctx.state === 'suspended') ctx.resume();
        return ctx;
    };

    // Unlock the audio context on the user's first interaction with the page
    // so the very first bell/gavel can play (browsers block audio pre-gesture).
    const unlock = () => {
        try { ensureCtx(); } catch (e) {}
        ['pointerdown', 'keydown', 'touchstart'].forEach(ev => document.removeEventListener(ev, unlock, true));
    };
    ['pointerdown', 'keydown', 'touchstart'].forEach(ev => document.addEventListener(ev, unlock, true));

    const playBell = () => {
        const c = ensureCtx(); if (!c) return;
        // Two-tone ding — bright, attention-grabbing
        [880, 1320].forEach((freq, i) => {
            const osc = c.createOscillator();
            const gain = c.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            const t0 = c.currentTime + i * 0.12;
            gain.gain.setValueAtTime(0.0001, t0);
            gain.gain.exponentialRampToValueAtTime(0.35, t0 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.7);
            osc.connect(gain).connect(c.destination);
            osc.start(t0);
            osc.stop(t0 + 0.75);
        });
    };

    // Airport-style 3-note PA chime — plays just before bidding opens
    const playChime = () => {
        const c = ensureCtx(); if (!c) return;
        const notes = [659.25, 523.25, 783.99]; // E5, C5, G5
        notes.forEach((freq, i) => {
            const osc = c.createOscillator();
            const gain = c.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            const t0 = c.currentTime + i * 0.45;
            gain.gain.setValueAtTime(0.0001, t0);
            gain.gain.exponentialRampToValueAtTime(0.3, t0 + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.0001, t0 + 1.1);
            osc.connect(gain).connect(c.destination);
            osc.start(t0);
            osc.stop(t0 + 1.15);
        });
    };

    const playGavel = () => {
        const c = ensureCtx(); if (!c) return;
        // Sharp noise-burst thump — wood on wood, with a low-end boom layered in
        const dur = 0.35;
        const buf = c.createBuffer(1, c.sampleRate * dur, c.sampleRate);
        const data = buf.getChannelData(0);
        for (let i = 0; i < data.length; i++) {
            data[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / data.length, 3);
        }
        const src = c.createBufferSource();
        src.buffer = buf;
        const filter = c.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.value = 900;
        filter.Q.value = 1.4;
        const noiseGain = c.createGain();
        noiseGain.gain.setValueAtTime(3.0, c.currentTime);
        noiseGain.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + dur);
        src.connect(filter).connect(noiseGain).connect(c.destination);
        src.start();

        // Low-end boom: a fast pitch drop from 160Hz → 55Hz gives it body
        const osc = c.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(160, c.currentTime);
        osc.frequency.exponentialRampToValueAtTime(55, c.currentTime + 0.12);
        const boomGain = c.createGain();
        boomGain.gain.setValueAtTime(1.4, c.currentTime);
        boomGain.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + dur);
        osc.connect(boomGain).connect(c.destination);
        osc.start();
        osc.stop(c.currentTime + dur);
    };

    const tickOnce = () => {
        const c = ensureCtx(); if (!c) return;
        const osc = c.createOscillator();
        const gain = c.createGain();
        osc.type = 'square';
        osc.frequency.value = 1600;
        gain.gain.setValueAtTime(0.0001, c.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12, c.currentTime + 0.005);
        gain.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + 0.04);
        osc.connect(gain).connect(c.destination);
        osc.start();
        osc.stop(c.currentTime + 0.05);
    };

    let tickIntervalMs = 1000;
    const startTick = (intervalMs = 1000) => {
        if (tickTimer && tickIntervalMs === intervalMs) return;
        if (tickTimer) { clearInterval(tickTimer); tickTimer = null; }
        tickIntervalMs = intervalMs;
        tickOnce();
        tickTimer = setInterval(tickOnce, intervalMs);
    };

    const stopTick = () => {
        if (tickTimer) { clearInterval(tickTimer); tickTimer = null; }
    };

    const setEnabled = (val) => {
        enabled = !!val;
        localStorage.setItem('liveSfxEnabled', enabled ? '1' : '0');
        if (!enabled) stopTick();
    };

    return {
        playBell, playChime, playGavel, startTick, stopTick,
        setEnabled,
        isEnabled: () => enabled,
    };
})();

// Live (Automated) Auction bidding + phase tracker.
// Drives the big phase banner, the 2-bidder validation window, and the mobile sticky bid bar.
Alpine.data('livePulse', (opts) => ({
    lotId: opts.lotId,
    currentBid: parseFloat(opts.currentBid),
    startingBid: parseFloat(opts.startingBid),
    increment: parseFloat(opts.increment),
    totalBids: opts.totalBids,
    hasTopBid: opts.hasTopBid,
    hasReserve: opts.hasReserve,
    reserveMet: opts.reserveMet,
    proxyMax: opts.proxyMax,
    proxyEnabled: opts.proxyEnabled,
    phase: opts.phase,
    phaseEndsAt: opts.phaseEndsAt,
    liveOpensAt: opts.liveOpensAt,
    distinctBidders: opts.distinctBidders,
    acceptsBids: opts.acceptsBids,
    isLoggedIn: opts.isLoggedIn,
    status: opts.status,
    userId: opts.userId || 0,
    winningBidderId: opts.winningBidderId || null,
    userHasBid: !!opts.userHasBid,
    isCommunity: !!opts.isCommunity,
    declinedAt: opts.declinedAt || null,

    // UI state
    bidAmount: 0,
    proxyAmount: '',
    submitting: false,
    bidFeedback: '',
    bidError: false,
    _phaseCountdown: 0,
    _tick: null,
    _poll: null,
    _lastPhase: null,
    sfxEnabled: window.liveSfx ? window.liveSfx.isEnabled() : true,

    toggleSfx() {
        this.sfxEnabled = !this.sfxEnabled;
        if (window.liveSfx) window.liveSfx.setEnabled(this.sfxEnabled);
    },

    _onPhaseChange(prev, next) {
        if (!window.liveSfx) return;
        // Stop any running tick when leaving pulse phases
        if ((prev === 'going_once' || prev === 'going_twice') && next !== 'going_once' && next !== 'going_twice') {
            window.liveSfx.stopTick();
        }
        if (next === 'presenting' && prev !== 'presenting') {
            window.liveSfx.playBell();
        } else if (next === 'open_call' && prev === 'presenting') {
            // Bidding has just opened — chime announces "the floor is yours"
            window.liveSfx.playChime();
        } else if (next === 'going_once' && prev !== 'going_once') {
            window.liveSfx.startTick(1000);
        } else if (next === 'going_twice' && prev !== 'going_twice') {
            // Double the pace for the final countdown
            window.liveSfx.startTick(500);
        } else if (next === 'closed' && prev !== 'closed') {
            window.liveSfx.stopTick();
            window.liveSfx.playGavel();
        }
    },

    // Community bid ladder — mirrors App\Helpers\BidLadder::TIERS.
    // Tiers: [minInclusive, maxExclusive, increment]
    _communityLadder: [
        [0, 100, 10],
        [100, 500, 25],
        [500, 1000, 50],
        [1000, 2500, 100],
        [2500, 10000, 250],
        [10000, 25000, 500],
        [25000, Infinity, 1000],
    ],

    nextIncrementFor(base) {
        const amount = Math.max(0, parseFloat(base) || 0);
        for (const [lo, hi, inc] of this._communityLadder) {
            if (amount >= lo && amount < hi) return inc;
        }
        return 1000;
    },

    bidForNSteps(steps) {
        if (!this.isCommunity) return this.minimumBid;
        let base = this.totalBids > 0 ? this.currentBid : 0;
        let amount = this.totalBids > 0 ? base : this.startingBid;
        for (let i = 0; i < Math.max(1, steps); i++) {
            if (i === 0 && this.totalBids === 0) continue; // first step = starting bid
            const inc = this.nextIncrementFor(amount);
            amount += inc;
        }
        return amount;
    },

    get minimumBid() {
        if (this.isCommunity) {
            if (this.totalBids === 0) return this.startingBid;
            return this.currentBid + this.nextIncrementFor(this.currentBid);
        }
        return this.totalBids > 0
            ? this.currentBid + this.increment
            : this.startingBid;
    },

    init() {
        this.bidAmount = this.minimumBid;
        this._lastPhase = this.phase;
        // If the page loads while already in a pulse phase, start the tick
        if (this.phase === 'going_once') {
            if (window.liveSfx) window.liveSfx.startTick(1000);
        } else if (this.phase === 'going_twice') {
            if (window.liveSfx) window.liveSfx.startTick(500);
        } else if (this.phase === 'presenting') {
            // Ring the bell for the very first lot on page load (no prior phase change
            // would have triggered it). Guarded by browser autoplay policy inside ensureCtx.
            if (window.liveSfx) window.liveSfx.playBell();
        }
        this.updateCountdown();
        this._tick = setInterval(() => this.updateCountdown(), 500);
        this._poll = setInterval(() => this.refetch(), 3000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) this.refetch();
            if (document.hidden && window.liveSfx) window.liveSfx.stopTick();
        });
    },

    destroy() {
        if (this._tick) clearInterval(this._tick);
        if (this._poll) clearInterval(this._poll);
        if (window.liveSfx) window.liveSfx.stopTick();
    },

    formatCurrency(v) {
        const sym = document.querySelector('meta[name="currency-symbol"]')?.content || 'R';
        return sym + Number(v || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    updateCountdown() {
        if (!this.phaseEndsAt) { this._phaseCountdown = 0; return; }
        const ms = new Date(this.phaseEndsAt).getTime() - Date.now();
        this._phaseCountdown = Math.max(0, Math.ceil(ms / 1000));
    },

    phaseLabel() {
        if (this.phase === 'closed') return this.closedResultLabel();
        return ({
            presenting: 'Presenting…',
            intermission: 'Next lot coming up',
            open_call: 'Who\'ll open?',
            active: 'Bidding open',
            going_once: 'Going once!',
            going_twice: 'Going twice!',
        })[this.phase] || '';
    },

    closedResultLabel() {
        const iAmWinner = this.userId > 0 && this.winningBidderId === this.userId;
        const iAmBidder = this.userId > 0 && this.userHasBid;

        // Community-specific branches
        if (this.isCommunity) {
            // Awaiting seller decision (24h window)
            if (this.status === 'pending_confirmation') {
                if (iAmWinner) return 'You won — awaiting seller confirmation';
                if (iAmBidder) return 'Better luck next time';
                return 'Sold — awaiting seller confirmation';
            }
            // Unsold: either seller declined, or 2-bidder rule failed
            // (community has no reserve, so those are the only two unsold-with-bids paths)
            if (this.status === 'unsold') {
                if (this.declinedAt) {
                    if (iAmWinner) return 'Seller declined — no payment due';
                    if (iAmBidder) return 'Better luck next time';
                    return 'Lot closed — unsold';
                }
                // No declined_at → 2-bidder rule failure (or no bids at all)
                if (iAmBidder) return 'Bidder limit not reached — no sale';
                return 'Lot closed — unsold';
            }
        }

        // Standard (non-community) + community confirmed sale
        if (this.status === 'sold' && iAmWinner) return 'Congratulations — you won!';
        if (iAmBidder) return 'Better luck next time';
        if (this.status === 'sold') return 'Sold';
        return 'Lot closed — unsold';
    },

    showPhaseCountdown() {
        if (this.phase === 'closed' || this.phase === 'going_once' || this.phase === 'going_twice') return false;
        return this._phaseCountdown > 0;
    },

    phaseCountdownLabel() {
        return this._phaseCountdown + 's';
    },

    bidButtonLabel() {
        if (this.phase === 'presenting') return 'Bidding opens in ' + this._phaseCountdown + 's';
        if (this.phase === 'intermission') return 'Next lot starting…';
        if (this.phase === 'closed') return 'Closed';
        if (this.hasTopBid && this.totalBids > 0) return 'Raise to ' + this.formatCurrency(this.minimumBid);
        return 'Bid ' + this.formatCurrency(this.minimumBid);
    },

    validationWindowLabel() {
        if (this.distinctBidders >= 2) return '2-bidder requirement satisfied.';
        if (this.isCommunity) {
            return 'Each lot needs 2 bidders to be valid — single-bidder lots go unsold.';
        }
        return 'Each lot needs 2 bidders to be valid (single bidder over reserve also wins).';
    },

    async refetch() {
        try {
            const res = await fetch(`/api/lots/${this.lotId}/status`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.currentBid !== undefined) this.currentBid = parseFloat(data.currentBid);
            if (data.totalBids !== undefined) this.totalBids = data.totalBids;
            if (data.reserveMet !== undefined) this.reserveMet = !!data.reserveMet;
            const prevPhase = this.phase;
            if (data.livePhase !== undefined) this.phase = data.livePhase;
            if (data.livePhaseEndsAt !== undefined) this.phaseEndsAt = data.livePhaseEndsAt;
            if (data.liveOpensAt !== undefined) this.liveOpensAt = data.liveOpensAt;
            if (data.distinctBidders !== undefined) this.distinctBidders = data.distinctBidders;
            if (data.acceptsBids !== undefined) this.acceptsBids = !!data.acceptsBids;
            if (data.status !== undefined) this.status = data.status;
            if (data.declinedAt !== undefined) this.declinedAt = data.declinedAt;
            if (data.winningBidderId !== undefined) {
                this.winningBidderId = data.winningBidderId;
                if (this.userId > 0) {
                    this.hasTopBid = this.winningBidderId === this.userId;
                }
            }
            if (this.bidAmount < this.minimumBid) this.bidAmount = this.minimumBid;
            if (data.status && data.status !== 'live' && this.phase !== 'closed') {
                // Lot closed — reflect it in phase and stop polling
                this.phase = 'closed';
            }
            if (this.phase !== prevPhase) {
                this._onPhaseChange(prevPhase, this.phase);
                this._lastPhase = this.phase;
            }
        } catch (e) { /* ignore */ }
    },

    async placeLiveBid(nSteps = 0) {
        if (!this.acceptsBids || this.submitting) return;
        let amount;
        if (this.isCommunity && nSteps > 0) {
            amount = this.bidForNSteps(nSteps);
        } else {
            amount = parseFloat(this.bidAmount);
        }
        if (!amount || amount < this.minimumBid) {
            this.bidFeedback = 'Minimum bid is ' + this.formatCurrency(this.minimumBid);
            this.bidError = true;
            return;
        }
        this.submitting = true;
        this.bidFeedback = '';
        try {
            const res = await fetch(`/api/lots/${this.lotId}/bid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                },
                body: JSON.stringify({ amount }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                this.bidFeedback = data.message || 'Bid failed';
                this.bidError = true;
            } else {
                this.bidFeedback = 'Bid placed!';
                this.bidError = false;
                this.currentBid = parseFloat(data.newBid);
                this.totalBids = data.totalBids;
                this.hasTopBid = !!data.hasTopBid;
                this.reserveMet = !!data.reserveMet;
                this.userHasBid = true;
                this.bidAmount = this.minimumBid;
                this.refetch();
            }
        } catch (e) {
            this.bidFeedback = 'Network error';
            this.bidError = true;
        } finally {
            this.submitting = false;
            setTimeout(() => { this.bidFeedback = ''; }, 3500);
        }
    },

    async setProxy() {
        const amount = parseFloat(this.proxyAmount);
        if (!amount || amount < this.minimumBid) {
            this.bidFeedback = 'Proxy must be at least ' + this.formatCurrency(this.minimumBid);
            this.bidError = true;
            return;
        }
        this.submitting = true;
        try {
            const res = await fetch(`/api/lots/${this.lotId}/proxy`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                },
                body: JSON.stringify({ max_amount: amount }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.proxyMax = amount;
                this.bidFeedback = 'Proxy set to ' + this.formatCurrency(amount);
                this.bidError = false;
                this.refetch();
            } else {
                this.bidFeedback = data.message || 'Could not set proxy';
                this.bidError = true;
            }
        } finally {
            this.submitting = false;
            setTimeout(() => { this.bidFeedback = ''; }, 3000);
        }
    },

    async cancelProxy() {
        try {
            const res = await fetch(`/api/lots/${this.lotId}/proxy`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                },
            });
            if (res.ok) {
                this.proxyMax = null;
                this.proxyAmount = '';
            }
        } catch (e) { /* ignore */ }
    },
}));

// Countdown Timer Component
Alpine.data('countdown', (endTime) => ({
    endTime: endTime,
    timeRemaining: 0,
    isUrgent: false,

    init() {
        this.updateCountdown();

        setInterval(() => {
            this.updateCountdown();
        }, 1000);

        // Re-sync immediately when tab becomes visible (mobile browsers throttle background timers)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateCountdown();
            }
        });
    },

    updateCountdown() {
        const now = new Date().getTime();
        const end = new Date(this.endTime).getTime();
        this.timeRemaining = Math.max(0, end - now);
        this.isUrgent = this.timeRemaining < 300000;
    },

    formatTime(ms) { return formatTimeMs(ms); }
}));

// Image Upload with Preview
Alpine.data('imageUpload', (maxImages = 1) => ({
    images: [],
    maxImages: maxImages,

    addImages(event) {
        const files = Array.from(event.target.files);
        const remaining = this.maxImages - this.images.length;

        if (files.length > remaining) {
            alert(`You can only upload ${remaining} more image(s)`);
            return;
        }

        files.forEach(file => {
            if (file.size > 5 * 1024 * 1024) {
                alert(`${file.name} is too large. Maximum size is 5MB`);
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert(`${file.name} is not an image`);
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                this.images.push({
                    file: file,
                    preview: e.target.result,
                    name: file.name
                });
            };
            reader.readAsDataURL(file);
        });
    },

    removeImage(index) {
        this.images.splice(index, 1);
    }
}));

// Notification Toast
Alpine.data('toast', () => ({
    visible: false,
    message: '',
    type: 'success', // success, error, warning, info

    show(message, type = 'success', duration = 3000) {
        this.message = message;
        this.type = type;
        this.visible = true;

        setTimeout(() => {
            this.hide();
        }, duration);
    },

    hide() {
        this.visible = false;
    }
}));

// Credits Monitor
Alpine.data('creditsMonitor', (initialBalance) => ({
    balance: initialBalance,

    async updateBalance() {
        try {
            const response = await fetch('/api/credits/balance');
            const data = await response.json();
            this.balance = data.balance;
        } catch (error) {
            console.error('Failed to update credits:', error);
        }
    },

    isLow() {
        return this.balance < 100;
    }
}));

// Map Component for Homepage
Alpine.data('auctioneerMap', () => ({
    map: null,
    markers: [],

    async init() {
        // Get container reference
        const container = this.$refs.mapContainer;

        // Check if already initialized (multiple ways)
        if (this.map ||
            container._leaflet_id ||
            container.classList.contains('leaflet-container')) {
            console.log('Map already initialized, skipping...');
            return;
        }

        // Initialize Leaflet map
        const L = await import('leaflet');

        // Fix default marker icon paths
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        });

        try {
            // Initialize centered on SA but fit to the full country bounding box so the whole country is visible
            // regardless of viewport size (phones auto-zoom out, desktops may show a bit of neighbouring countries).
            this.map = L.map(container);
            const saBounds = [
                [-35.0, 16.3],  // SW corner (Cape Agulhas / Atlantic)
                [-22.0, 33.0],  // NE corner (Limpopo / Mozambique border)
            ];
            this.map.fitBounds(saBounds, { padding: [10, 10] });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(this.map);

            // Force map to recalculate its size
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                    this.loadAuctioneers();
                }
            }, 100);
        } catch (error) {
            console.error('Map initialization error:', error);
        }
    },

    async loadAuctioneers() {
        try {
            const response = await fetch('/api/auctioneers/map');
            const auctioneers = await response.json();

            const L = await import('leaflet');

            const gavelIcon = L.icon({
                iconUrl: '/images/gavel-logo.svg',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            });

            // Community hub pin — teardrop pin with a white inner circle holding
            // three head-and-shoulder silhouettes (the local community).
            const communityIconSvg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="42" viewBox="0 0 36 42">
                    <path d="M18 0C8.06 0 0 8.06 0 18c0 13.5 18 24 18 24s18-10.5 18-24C36 8.06 27.94 0 18 0z" fill="#0d9488" stroke="#134e4a" stroke-width="1.5"/>
                    <circle cx="18" cy="17" r="11" fill="#ffffff"/>
                    <!-- Left figure -->
                    <circle cx="10.5" cy="14" r="1.8" fill="#0d9488"/>
                    <path d="M 8 21 Q 8 17 10.5 17 Q 13 17 13 21 Z" fill="#0d9488"/>
                    <!-- Middle figure (slightly forward) -->
                    <circle cx="18" cy="13" r="2.2" fill="#0d9488"/>
                    <path d="M 14.5 22 Q 14.5 16.5 18 16.5 Q 21.5 16.5 21.5 22 Z" fill="#0d9488"/>
                    <!-- Right figure -->
                    <circle cx="25.5" cy="14" r="1.8" fill="#0d9488"/>
                    <path d="M 23 21 Q 23 17 25.5 17 Q 28 17 28 21 Z" fill="#0d9488"/>
                </svg>`;
            const communityIcon = L.divIcon({
                html: communityIconSvg,
                className: 'community-map-pin',
                iconSize: [36, 42],
                iconAnchor: [18, 42],
                popupAnchor: [0, -42],
            });

            auctioneers.forEach(auctioneer => {
                const isCommunity = auctioneer.type === 'community';
                const icon = isCommunity ? communityIcon : gavelIcon;
                const badge = isCommunity
                    ? '<span class="inline-block px-1.5 py-0.5 text-[10px] bg-teal-100 text-teal-800 rounded uppercase tracking-wide mb-1">Community</span>'
                    : '';
                const profileUrl = isCommunity
                    ? `/community/region/${auctioneer.slug.replace('community-', '')}`
                    : `/auctioneer/${auctioneer.slug}`;
                const linkLabel = isCommunity ? 'View Community' : 'View Profile';
                const marker = L.marker([auctioneer.lat, auctioneer.lng], { icon })
                    .addTo(this.map)
                    .bindPopup(`
                        <div class="p-2">
                            ${badge}
                            <h3 class="font-bold">${auctioneer.name}</h3>
                            <p class="text-sm text-gray-600">${auctioneer.location}</p>
                            <a href="${profileUrl}" class="text-primary-600 text-sm hover:underline">${linkLabel}</a>
                        </div>
                    `);

                this.markers.push(marker);
            });
        } catch (error) {
            console.error('Failed to load auctioneers:', error);
        }
    },

    destroy() {
        // Clean up map when component is destroyed
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        this.markers = [];
    }
}));

// Mobile account drawer store
Alpine.store('mobileMenu', { open: false });

// In-app Notification Bell Store (polling-based, SwitchSA pattern)
Alpine.store('bell', {
    open: false,
    count: 0,
    notifications: [],
    modal: false,
    viewing: null,
    tab: 'new',
    history: [],
    historyLoaded: false,
    _polling: null,
    _started: false,

    start() {
        if (this._started) return;
        this._started = true;
        this.doFetch();
        this._polling = setInterval(() => this.doFetch(), 300000);

        // Pause polling when tab is hidden (saves server load on mobile)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(this._polling);
                this._polling = null;
            } else {
                this.doFetch();
                if (!this._polling) this._polling = setInterval(() => this.doFetch(), 300000);
            }
        });
    },

    toggle() {
        this.open = !this.open;
        if (this.open) {
            this.tab = 'new';
            this.doFetch();
        }
    },

    close() {
        this.open = false;
    },

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    async doFetch() {
        try {
            const res = await window.fetch('/api/notifications/unread');
            if (!res.ok) return;
            const data = await res.json();
            // Only update state if count actually changed (avoids unnecessary re-renders)
            if (this.count !== data.count) {
                this.count = data.count;
                this.notifications = data.notifications;
                this.updateAppBadge();
            }
        } catch (e) {}
    },

    // Sets the OS-level app icon badge (e.g. WhatsApp/Facebook-style red circle with number)
    // via the Badging API. Silently no-ops on browsers/platforms that don't support it.
    updateAppBadge() {
        try {
            if (this.count > 0 && navigator.setAppBadge) {
                navigator.setAppBadge(this.count).catch(() => {});
            } else if (navigator.clearAppBadge) {
                navigator.clearAppBadge().catch(() => {});
            }
        } catch (e) {}
    },

    async loadHistory() {
        try {
            const res = await window.fetch('/api/notifications/history');
            if (!res.ok) return;
            this.history = await res.json();
            this.historyLoaded = true;
        } catch (e) {}
    },

    switchTab(t) {
        this.tab = t;
        if (t === 'history' && !this.historyLoaded) this.loadHistory();
    },

    viewNotification(n) {
        this.viewing = n;
        this.modal = true;
        this.open = false;
        this.markRead(n.id);
    },

    closeModal() {
        this.modal = false;
        this.viewing = null;
    },

    goToLink() {
        if (this.viewing?.link) window.location.href = this.viewing.link;
        this.closeModal();
    },

    async markRead(id) {
        try {
            await window.fetch(`/api/notifications/${id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf() } });
            this.notifications = this.notifications.filter(n => n.id !== id);
            this.count = Math.max(0, this.count - 1);
            if (this.historyLoaded) {
                const h = this.history.find(n => n.id === id);
                if (h) h.read = true;
            }
            this.updateAppBadge();
        } catch (e) {}
    },

    async markAllRead() {
        try {
            await window.fetch('/api/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf() } });
            this.notifications = [];
            this.count = 0;
            if (this.historyLoaded) this.history.forEach(n => n.read = true);
            this.updateAppBadge();
        } catch (e) {}
    },

    typeColor(type) {
        return { info: '#3b82f6', success: '#22c55e', warning: '#f59e0b', urgent: '#ef4444' }[type] || '#3b82f6';
    },
});

// Push Notification Banner Component
Alpine.data('pushBanner', () => ({
    showBanner: false,

    init() {
        // Don't show if browser doesn't support push
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            return;
        }

        const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
        if (!vapidKey) return;

        if (Notification.permission === 'granted') {
            // Already granted — silently sync subscription to server
            subscribeToPush();
            return;
        }

        if (Notification.permission === 'denied') {
            // User blocked notifications — nothing we can do
            return;
        }

        // New registration — skip banner and prompt immediately
        if (document.querySelector('meta[name="push-prompt"]')) {
            subscribeToPush();
            return;
        }

        // Permission is 'default' — check if user dismissed the banner recently
        const dismissed = localStorage.getItem('push_banner_dismissed');
        if (dismissed) {
            const dismissedAt = parseInt(dismissed, 10);
            // Show again after 7 days
            if (Date.now() - dismissedAt < 7 * 24 * 60 * 60 * 1000) {
                return;
            }
        }

        // Show the banner after a short delay
        setTimeout(() => { this.showBanner = true; }, 3000);
    },

    async enable() {
        this.showBanner = false;
        await subscribeToPush();
    },

    dismiss() {
        this.showBanner = false;
        localStorage.setItem('push_banner_dismissed', Date.now().toString());
    }
}));

// Push Notification Subscribe Helper
async function subscribeToPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidKey) return;

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            const applicationServerKey = urlBase64ToUint8Array(vapidKey);
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey,
            });
        }

        const subJson = subscription.toJSON();
        console.log('Push: sending subscription to server, endpoint=' + subJson.endpoint.substring(0, 60));
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                endpoint: subJson.endpoint,
                keys: {
                    p256dh: subJson.keys.p256dh,
                    auth: subJson.keys.auth,
                },
            }),
        });
        const data = await response.json();
        console.log('Push: server response', response.status, data);
    } catch (error) {
        console.error('Push subscription failed:', error);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

window.subscribeToPush = subscribeToPush;

// Register Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
}

// Expose Alpine globally
window.Alpine = Alpine;

// Supplier ID document capture — camera on desktop via getUserMedia,
// native camera on mobile via the input's capture attribute.
Alpine.data('idCaptureComponent', () => ({
    cameraOpen: false,
    stream: null,
    filename: '',
    error: '',

    isMobile() {
        return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
    },

    pickFile() {
        const el = document.getElementById('supplier_id_document');
        el.removeAttribute('capture');
        el.click();
    },

    async takePhoto() {
        // Mobile: use native camera via input[capture] — better UX, no permissions dance.
        if (this.isMobile()) {
            const el = document.getElementById('supplier_id_document');
            el.setAttribute('capture', 'environment');
            el.click();
            return;
        }

        // Desktop: use getUserMedia to show live webcam preview.
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.error = 'Your browser does not support camera access. Please use Upload File instead.';
            return;
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 960 } },
                audio: false,
            });
            this.cameraOpen = true;
            this.error = '';
            this.$nextTick(() => {
                const video = this.$refs.video;
                if (video) {
                    video.srcObject = this.stream;
                    video.play().catch(() => {});
                }
            });
        } catch (err) {
            this.error = 'Could not access camera: ' + (err.message || err.name || 'permission denied');
        }
    },

    closeCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(t => t.stop());
            this.stream = null;
        }
        this.cameraOpen = false;
    },

    capturePhoto() {
        const video = this.$refs.video;
        const canvas = this.$refs.canvas;
        if (!video || !canvas) return;

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        canvas.toBlob((blob) => {
            if (!blob) {
                this.error = 'Failed to capture image.';
                return;
            }
            const file = new File([blob], 'supplier-id-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            const input = document.getElementById('supplier_id_document');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            // Fire change event so any external listeners update
            input.dispatchEvent(new Event('change', { bubbles: true }));
            this.filename = file.name;
            this.closeCamera();
        }, 'image/jpeg', 0.9);
    },

    handleFileChange(event) {
        const f = event.target.files[0];
        this.filename = f ? f.name : '';
    },
}));

// Supplier picker — typeahead search across name/UID/ID number, scoped to current
// auctioneer. Three modes: 'search' (default), 'selected' (picked an existing
// supplier; sends supplier_id and collapses free-text fields), 'new' (expanded
// name/id/address/doc fields for creating). `initial` is optional and used on
// the edit form when a supplier is already linked to the lot.
Alpine.data('supplierPicker', (initial = null) => ({
    mode: initial && initial.id ? 'selected' : 'search',
    supplierId: initial && initial.id ? initial.id : '',
    selected: initial && initial.id ? {
        id: initial.id,
        uid: initial.uid || '',
        name: initial.name || '',
        id_number_last4: initial.id_number_last4 || null,
    } : null,
    query: '',
    results: [],
    loading: false,
    searchedOnce: false,
    _timer: null,

    onQueryInput() {
        clearTimeout(this._timer);
        const q = this.query.trim();
        if (q.length < 2) {
            this.results = [];
            this.searchedOnce = false;
            return;
        }
        this._timer = setTimeout(() => this._fetch(q), 250);
    },

    async _fetch(q) {
        this.loading = true;
        try {
            const res = await fetch('/api/suppliers/search?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (res.ok) {
                const data = await res.json();
                this.results = data.results || [];
            } else {
                this.results = [];
            }
        } catch (e) {
            this.results = [];
        } finally {
            this.loading = false;
            this.searchedOnce = true;
        }
    },

    pickResult(r) {
        this.selected = r;
        this.supplierId = r.id;
        this.mode = 'selected';
        this.results = [];
        this.query = '';
    },

    enterNew() {
        // Pre-fill the "name" input on the new-entry form with whatever they typed,
        // so a typo'd search still saves keystrokes.
        this.mode = 'new';
        this.$nextTick(() => {
            const nameEl = document.getElementById('supplier_name');
            if (nameEl && this.query && !nameEl.value) {
                nameEl.value = this.query;
            }
        });
    },

    backToSearch() {
        this.mode = 'search';
        this.results = [];
    },

    unlink() {
        this.selected = null;
        this.supplierId = '';
        this.mode = 'search';
        this.query = '';
        this.results = [];
    },
}));

// Start Alpine when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    Alpine.start();
}

// Auto-start notification bell after Alpine is ready (for authenticated users only)
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (document.querySelector('meta[name="user-authenticated"]')) {
            Alpine.store('bell')?.start();
        }
    }, 500);
});
