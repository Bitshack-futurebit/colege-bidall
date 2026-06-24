<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TermsVersion extends Model
{
    protected $fillable = [
        'version',
        'title',
        'role',
        'content',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptances()
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->orderByDesc('published_at');
    }

    public function isDraft(): bool
    {
        return is_null($this->published_at);
    }

    public function isPublished(): bool
    {
        return !is_null($this->published_at);
    }

    /**
     * Get the current published terms for a specific role.
     * Falls back to general terms (role = null) if no role-specific terms exist.
     */
    public static function currentForRole(?string $role = null): ?self
    {
        $cacheKey = 'current_terms_' . ($role ?? 'general');

        return Cache::remember($cacheKey, 300, function () use ($role) {
            // Try role-specific terms first
            if ($role) {
                $terms = static::published()->where('role', $role)->first();
                if ($terms) {
                    return $terms;
                }
            }

            // Fall back to general terms
            return static::published()->whereNull('role')->first();
        });
    }

    /**
     * Get the current general terms (for public /terms page).
     */
    public static function current(): ?self
    {
        return Cache::remember('current_terms_version', 300, function () {
            return static::published()->whereNull('role')->first();
        });
    }

    /**
     * Get all current terms a user needs to accept based on their role.
     */
    public static function currentForUser(User $user): array
    {
        $terms = [];

        // General terms
        $general = static::current();
        if ($general) {
            $terms[] = $general;
        }

        // Role-specific terms
        $roleTerms = static::currentForRole($user->role);
        if ($roleTerms && (!$general || $roleTerms->id !== $general->id)) {
            $terms[] = $roleTerms;
        }

        return $terms;
    }

    public static function clearCache(): void
    {
        Cache::forget('current_terms_version');
        Cache::forget('current_terms_bidder');
        Cache::forget('current_terms_auctioneer');
        Cache::forget('current_terms_general');
        Cache::forget('current_terms_staff');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'bidder' => 'Bidders',
            'auctioneer' => 'Auctioneers',
            'staff' => 'Staff',
            null => 'All Users',
            default => ucfirst($this->role),
        };
    }
}
