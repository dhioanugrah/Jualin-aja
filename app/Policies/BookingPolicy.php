<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Product;
use App\Models\User;

/**
 * Hak akses booking: hanya pembeli dan penjual booking itu (plus admin untuk melihat).
 * Aturan waktu dan status yang lebih rinci ada di BookingService.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->involves($user) || $user->isAdmin();
    }

    /**
     * Akun terverifikasi boleh booking barang orang lain yang masih tersedia.
     */
    public function create(User $user, Product $product): bool
    {
        return $user->hasVerifiedEmail()
            && ! $user->isAdmin()
            && $user->id !== $product->user_id
            && $product->isAvailable();
    }

    public function respond(User $user, Booking $booking): bool
    {
        return $booking->isSeller($user) && $booking->status === BookingStatus::Pending;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->involves($user) && $booking->isActive();
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $booking->involves($user) && $booking->status === BookingStatus::Accepted;
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $booking->involves($user) && $booking->status === BookingStatus::Accepted;
    }

    public function reportNoShow(User $user, Booking $booking): bool
    {
        return $booking->involves($user) && $booking->status === BookingStatus::Accepted;
    }

    public function review(User $user, Booking $booking): bool
    {
        return $booking->canBeReviewedBy($user);
    }

    public function dispute(User $user, Booking $booking): bool
    {
        return $booking->canBeDisputedBy($user);
    }
}
