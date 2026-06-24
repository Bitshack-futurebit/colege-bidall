<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\CreateNextCommunityAuction;
use App\Http\Controllers\Controller;
use App\Models\Auctioneer;
use App\Models\CommunityRegion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityRegionsController extends Controller
{
    public function index()
    {
        $regions = CommunityRegion::withCount([
            'users as bidder_count' => fn ($q) => $q->where('is_active', true),
            'auctions as auction_count',
            'schedules as schedule_count' => fn ($q) => $q->where('is_active', true),
        ])->with(['schedules' => fn ($q) => $q->where('is_active', true)->orderBy('goes_live_day')->orderBy('goes_live_time')])
          ->orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.community-regions.index', compact('regions'));
    }

    public function create()
    {
        return view('admin.community-regions.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateRegion($request);
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $region = CommunityRegion::create($validated);

        // Materialise the system auctioneer immediately so the region shows up
        // on the public map and communities toggle without waiting for the
        // weekly scheduler to run.
        app(CreateNextCommunityAuction::class)->systemAuctioneerFor($region);
        $this->syncSystemAuctioneerCoords($region);
        \Cache::forget('auctioneers_map_data_v2');

        return redirect()->route('admin.community-regions.index')
            ->with('success', 'Community region created.');
    }

    public function edit(CommunityRegion $region)
    {
        $region->load(['schedules' => fn ($q) => $q->orderBy('goes_live_day')->orderBy('goes_live_time')]);

        // Materialise the system auctioneer if it's somehow missing (older regions
        // created before we added auto-creation on store), then pass through.
        $auctioneer = Auctioneer::where('slug', 'community-' . $region->slug)->first();
        if (!$auctioneer) {
            $auctioneer = app(CreateNextCommunityAuction::class)->systemAuctioneerFor($region);
        }

        return view('admin.community-regions.edit', compact('region', 'auctioneer'));
    }

    /**
     * Edit the public-facing community profile (logo, banner, description,
     * WhatsApp group link). Targets the underlying system Auctioneer record
     * — that's what the public auctioneer.show page renders.
     */
    public function updateProfile(Request $request, CommunityRegion $region)
    {
        $auctioneer = Auctioneer::where('slug', 'community-' . $region->slug)->first();
        if (!$auctioneer) {
            $auctioneer = app(CreateNextCommunityAuction::class)->systemAuctioneerFor($region);
        }

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'whatsapp_group_link' => ['nullable', 'url', 'max:500'],
            'logo' => ['nullable', 'image', 'max:8192'],
            'banner_image' => ['nullable', 'image', 'max:8192'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_banner' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && $auctioneer->logo) {
            Storage::disk('public')->delete($auctioneer->logo);
            $auctioneer->logo = null;
        } elseif ($request->hasFile('logo')) {
            if ($auctioneer->logo) Storage::disk('public')->delete($auctioneer->logo);
            $auctioneer->logo = $request->file('logo')->store('auctioneers/logos', 'public');
        }

        if ($request->boolean('remove_banner') && $auctioneer->banner_image) {
            Storage::disk('public')->delete($auctioneer->banner_image);
            $auctioneer->banner_image = null;
        } elseif ($request->hasFile('banner_image')) {
            if ($auctioneer->banner_image) Storage::disk('public')->delete($auctioneer->banner_image);
            $auctioneer->banner_image = $request->file('banner_image')->store('auctioneers/banners', 'public');
        }

        $auctioneer->description = $validated['description'] ?? null;
        if (array_key_exists('whatsapp_number', $validated)) {
            $auctioneer->whatsapp_number = $validated['whatsapp_number'] ?: '0000000000';
        }
        $auctioneer->whatsapp_group_link = $validated['whatsapp_group_link'] ?? null;
        $auctioneer->save();

        \Cache::forget('auctioneers_map_data_v2');

        return redirect()->route('admin.community-regions.edit', $region)
            ->with('success', 'Community public profile updated.');
    }

    public function update(Request $request, CommunityRegion $region)
    {
        $validated = $this->validateRegion($request, $region);
        $region->update($validated);
        $this->syncSystemAuctioneerCoords($region);
        \Cache::forget('auctioneers_map_data_v2');

        return redirect()->route('admin.community-regions.edit', $region)
            ->with('success', 'Community region updated.');
    }

    public function toggleActive(CommunityRegion $region)
    {
        $region->update(['is_active' => !$region->is_active]);

        return back()->with('success', 'Region ' . ($region->is_active ? 'activated.' : 'deactivated.'));
    }

    public function togglePilot(CommunityRegion $region)
    {
        $region->update(['pilot_mode' => !$region->pilot_mode]);

        return back()->with('success', 'Pilot mode ' . ($region->pilot_mode ? 'enabled.' : 'disabled.'));
    }

    public function destroy(CommunityRegion $region)
    {
        if ($region->auctions()->whereIn('status', ['live', 'upcoming'])->exists()) {
            return back()->with('error', 'Cannot delete a region with a live or upcoming auction. End it first.');
        }

        // Soft-delete all auctions in this region so they disappear from public
        // listings. Schedules are region-owned and hard-deleted via cascade.
        $auctionCount = $region->auctions()->delete();
        $region->delete();

        $msg = $auctionCount > 0
            ? "Community region deleted along with {$auctionCount} past auction(s)."
            : 'Community region deleted.';

        return redirect()->route('admin.community-regions.index')->with('success', $msg);
    }

    private function validateRegion(Request $request, ?CommunityRegion $region = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'metro_area' => ['nullable', 'string', 'max:120'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'pilot_mode' => ['nullable', 'boolean'],
            'bidder_threshold' => ['required', 'integer', 'min:0'],
            'min_lots_for_viability' => ['required', 'integer', 'min:1'],
            'min_bidders_for_viability' => ['required', 'integer', 'min:1'],
            'listing_limit_per_week' => ['nullable', 'integer', 'min:1', 'max:50'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * Sync the system auctioneer's user lat/lng to match this region — so the
     * existing /api/auctioneers/map query (which filters on user.lat/lng) picks
     * up the community pin without needing a separate code path.
     */
    private function syncSystemAuctioneerCoords(CommunityRegion $region): void
    {
        $auctioneer = \App\Models\Auctioneer::where('slug', 'community-' . $region->slug)->first();
        if (!$auctioneer || !$auctioneer->user) return;
        $auctioneer->user->update([
            'lat' => $region->lat,
            'lng' => $region->lng,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (CommunityRegion::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
