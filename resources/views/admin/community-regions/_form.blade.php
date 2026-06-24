@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="label">Name</label>
        <input type="text" name="name" value="{{ old('name', $region->name ?? '') }}" required maxlength="120" class="input">
        <p class="text-xs text-gray-500 mt-1">Displayed on listings and auction pages (e.g. "Lower South Coast").</p>
        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="label">Metro Area</label>
        <input type="text" name="metro_area" value="{{ old('metro_area', $region->metro_area ?? '') }}" maxlength="120" class="input">
        <p class="text-xs text-gray-500 mt-1">Broader grouping (e.g. "KZN South").</p>
    </div>

    <div>
        <label class="label">Latitude</label>
        <input type="number" step="0.0000001" min="-90" max="90"
               name="lat" value="{{ old('lat', $region->lat ?? '') }}" class="input"
               placeholder="-30.7411">
        <p class="text-xs text-gray-500 mt-1">Map pin position. Right-click in Google Maps → coordinates copy.</p>
    </div>

    <div>
        <label class="label">Longitude</label>
        <input type="number" step="0.0000001" min="-180" max="180"
               name="lng" value="{{ old('lng', $region->lng ?? '') }}" class="input"
               placeholder="30.4467">
        <p class="text-xs text-gray-500 mt-1">Leave both blank to hide the community from the map.</p>
    </div>

    <div>
        <label class="label">Bidder Threshold</label>
        <input type="number" name="bidder_threshold" min="0" value="{{ old('bidder_threshold', $region->bidder_threshold ?? 50) }}" required class="input">
        <p class="text-xs text-gray-500 mt-1">Registered bidders before pilot mode ends automatically.</p>
    </div>

    <div>
        <label class="label">Min Lots for Viability</label>
        <input type="number" name="min_lots_for_viability" min="1" value="{{ old('min_lots_for_viability', $region->min_lots_for_viability ?? 5) }}" required class="input">
        <p class="text-xs text-gray-500 mt-1">Auction rolls to next week if below this.</p>
    </div>

    <div>
        <label class="label">Min Bidders for Viability</label>
        <input type="number" name="min_bidders_for_viability" min="1" value="{{ old('min_bidders_for_viability', $region->min_bidders_for_viability ?? 20) }}" required class="input">
    </div>

    <div>
        <label class="label">Weekly Listing Limit per Seller</label>
        <input type="number" name="listing_limit_per_week" min="1" max="50"
               value="{{ old('listing_limit_per_week', $region->listing_limit_per_week ?? '') }}" class="input"
               placeholder="Default: {{ config('community.listing_limit_per_week', 3) }}">
        <p class="text-xs text-gray-500 mt-1">Max lots a seller can list per week in this region. Leave blank to use platform default ({{ config('community.listing_limit_per_week', 3) }}).</p>
    </div>

    <div class="md:col-span-2">
        <label class="label">Description</label>
        <textarea name="description" rows="2" maxlength="2000" class="input">{{ old('description', $region->description ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">Shown publicly on the region landing page (e.g. "Port Shepstone, Margate, Shelly Beach…").</p>
    </div>

    <div class="md:col-span-2">
        <label class="label">Admin Notes</label>
        <textarea name="admin_notes" rows="2" maxlength="2000" class="input">{{ old('admin_notes', $region->admin_notes ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">Internal-only tracking notes.</p>
    </div>

    <div class="flex gap-6">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $region->is_active ?? true) ? 'checked' : '' }} class="rounded">
            <span class="text-sm">Active (accepting listings & scheduling auctions)</span>
        </label>

        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="pilot_mode" value="0">
            <input type="checkbox" name="pilot_mode" value="1" {{ old('pilot_mode', $region->pilot_mode ?? true) ? 'checked' : '' }} class="rounded">
            <span class="text-sm">Pilot mode (relaxed decline ladder: 2 per 30 days)</span>
        </label>
    </div>
</div>
