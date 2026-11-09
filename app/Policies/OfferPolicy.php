<?php

namespace App\Policies;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;

class OfferPolicy
{
    /**
     * Barang donasi tidak bisa ditawar; penjual tidak bisa menawar barangnya sendiri.
     */
    public function create(User $user, Product $product): bool
    {
        return $user->hasVerifiedEmail()
            && ! $user->isAdmin()
            && $user->id !== $product->user_id
            && ! $product->is_donation
            && $product->isAvailable();
    }

    public function respond(User $user, Offer $offer): bool
    {
        return $user->id === $offer->product->user_id && $offer->status === OfferStatus::Pending;
    }

    public function cancel(User $user, Offer $offer): bool
    {
        return $user->id === $offer->buyer_id
            && in_array($offer->status, [OfferStatus::Pending, OfferStatus::Accepted], true);
    }
}
