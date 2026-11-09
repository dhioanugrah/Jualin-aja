<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\OfferStatus;
use App\Enums\ProductStatus;
use App\Enums\UserMode;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dasbor Lapak, halaman awal Mode Penjual (F4).
 */
class SellerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $counts = $user->products()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('seller.dashboard', [
            'stats' => [
                'active' => (int) ($counts[ProductStatus::Approved->value] ?? 0),
                'pending' => (int) ($counts[ProductStatus::Pending->value] ?? 0),
                'needsRevision' => (int) ($counts[ProductStatus::NeedsRevision->value] ?? 0),
                'sold' => (int) ($counts[ProductStatus::Sold->value] ?? 0),
                'views' => (int) $user->products()->sum('views'),
                'pendingBookings' => $user->sales()->where('status', BookingStatus::Pending)->count(),
                'upcomingBookings' => $user->sales()->where('status', BookingStatus::Accepted)->count(),
                'promotionQuota' => $user->promotionQuotaLeft(),
                'activePromotions' => $user->promotions()->running()->count(),
                'buyerRating' => $user->ratingAs(UserMode::Buyer),
                'pendingOffers' => Offer::query()
                    ->where('status', OfferStatus::Pending)
                    ->whereHas('product', fn ($query) => $query->where('user_id', $user->id))
                    ->count(),
            ],
            'needsAttention' => $user->products()
                ->where('status', ProductStatus::NeedsRevision)
                ->with('cover')
                ->latest('updated_at')
                ->get(),
            'nextPromotionAt' => $user->nextPromotionAt(),
            'recent' => $user->products()
                ->withPromotedFlag()
                ->with(['cover', 'seller'])
                ->latest('updated_at')
                ->take(6)
                ->get(),
        ]);
    }
}
