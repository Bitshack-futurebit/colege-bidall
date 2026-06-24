<?php

namespace App\Services;

use App\Models\Auctioneer;
use Illuminate\Support\Facades\Storage;

class WhiteLabelContext
{
    private ?Auctioneer $auctioneer = null;

    public function activate(Auctioneer $auctioneer): void
    {
        $this->auctioneer = $auctioneer;
    }

    public function isActive(): bool
    {
        return $this->auctioneer !== null;
    }

    public function auctioneer(): ?Auctioneer
    {
        return $this->auctioneer;
    }

    public function primaryColor(): ?string
    {
        return $this->auctioneer?->brand_primary_color;
    }

    public function secondaryColor(): ?string
    {
        return $this->auctioneer?->brand_secondary_color;
    }

    public function logoUrl(): ?string
    {
        if ($this->auctioneer?->logo) {
            return Storage::url($this->auctioneer->logo);
        }
        return null;
    }

    public function businessName(): string
    {
        return $this->auctioneer?->business_name ?? config('branding.name');
    }

    public function faviconUrl(): ?string
    {
        if ($this->auctioneer?->brand_favicon) {
            return Storage::url($this->auctioneer->brand_favicon);
        }
        return null;
    }

    public function heroText(): ?string
    {
        return $this->auctioneer?->brand_hero_text;
    }

    public function slug(): ?string
    {
        return $this->auctioneer?->slug;
    }
}
