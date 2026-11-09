<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserMode;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

/**
 * Halaman lapak penjual (F6): semua barang tayang, rating, ulasan, dan skor keandalan.
 */
class SellerStoreController extends Controller
{
    public function __invoke(User $user): View
    {
        abort_if(! $user->is_active || $user->isAdmin(), 404);

        $reviews = $user->sellerReviews()->with('reviewer')->latest()->take(6)->get();

        return view('sellers.show', [
            'seller' => $user,
            'products' => Product::query()
                ->visible()
                ->whereBelongsTo($user, 'seller')
                ->withPromotedFlag()
                ->with(['cover', 'seller'])
                ->latest()
                ->paginate((int) config('jualin.catalog_per_page')),
            'rating' => $user->ratingAs(UserMode::Seller),
            'buyerRating' => $user->ratingAs(UserMode::Buyer),
            'topTags' => $user->sellerReviews()->pluck('tags')->flatten()->filter()->countBy()->sortDesc()->take(3)->keys(),
            'reviews' => $reviews,
            'reliability' => $user->reliability(UserMode::Seller),
            'soldCount' => $user->sales()->where('status', BookingStatus::Completed)->count(),
        ]);
    }
}
