<?php

namespace App\Http\Controllers;

use App\Enums\ReviewDirection;
use App\Http\Requests\ReviewRequest;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use Illuminate\Http\RedirectResponse;

/**
 * Rating dua arah (F11): setelah booking Selesai, pembeli menilai penjual dan penjual menilai pembeli.
 */
class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        $direction = $booking->reviewDirectionFor($user);
        $revieweeId = $booking->counterpartIdOf($user);

        $booking->reviews()->create([
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'direction' => $direction,
            'rating' => $request->integer('rating'),
            'comment' => $request->validated('comment'),
            'tags' => array_values($request->validated('tags') ?? []) ?: null,
        ]);

        User::query()->find($revieweeId)?->notify(new TransactionUpdate(
            'Ulasan baru untukmu',
            "{$user->name} memberi {$request->integer('rating')} bintang untuk transaksi \"{$booking->product->name}\".",
            $direction === ReviewDirection::BuyerToSeller ? route('sellers.show', $revieweeId) : route('bookings.show', $booking),
            'success',
        ));

        return back()->with('toast', 'Terima kasih! Ulasanmu tersimpan.');
    }
}
