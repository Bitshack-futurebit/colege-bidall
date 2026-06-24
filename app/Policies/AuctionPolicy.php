<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    /**
     * Determine if the user can view the auction.
     */
    public function view(User $user, Auction $auction): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAuctioneer() && $user->auctioneer && $user->auctioneer->id === $auction->auctioneer_id) {
            return true;
        }

        // Staff with active membership on the same auctioneer
        if ($user->isStaff() && $user->staffMembership && $user->staffMembership->is_active
            && $user->staffMembership->auctioneer_id === $auction->auctioneer_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update the auction.
     */
    public function update(User $user, Auction $auction): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAuctioneer() && $user->auctioneer && $user->auctioneer->id === $auction->auctioneer_id) {
            return true;
        }

        // Staff with lot or auction management permissions
        if ($user->isStaff() && $user->staffMembership && $user->staffMembership->is_active
            && $user->staffMembership->auctioneer_id === $auction->auctioneer_id) {
            return $user->staffMembership->canManageLots() || $user->staffMembership->canManageAuctions();
        }

        return false;
    }

    /**
     * Determine if the user can delete the auction.
     */
    public function delete(User $user, Auction $auction): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAuctioneer() && $user->auctioneer && $user->auctioneer->id === $auction->auctioneer_id && $auction->status === 'draft') {
            return true;
        }

        // Staff with auction management permission (draft only)
        if ($user->isStaff() && $user->staffMembership && $user->staffMembership->is_active
            && $user->staffMembership->auctioneer_id === $auction->auctioneer_id
            && $user->staffMembership->canManageAuctions()
            && $auction->status === 'draft') {
            return true;
        }

        return false;
    }
}
