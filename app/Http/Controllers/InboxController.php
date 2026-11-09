<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pesan / kotak masuk transaksi (F10). Tab Masuk berisi penawaran dan booking
 * untuk barang saya; tab Keluar berisi yang saya ajukan ke penjual lain.
 */
class InboxController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab') ?? ($user->isSellerMode() ? 'masuk' : 'keluar');
        $tab = in_array($tab, ['masuk', 'keluar'], true) ? $tab : 'keluar';
        $incoming = $tab === 'masuk';

        $bookings = Booking::query()
            ->when(
                $incoming,
                fn (Builder $query) => $query->where('seller_id', $user->id),
                fn (Builder $query) => $query->where('buyer_id', $user->id),
            )
            ->with(['product.cover', 'buyer', 'seller', 'codLocation'])
            ->orderByRaw("case when status in ('pending', 'accepted') then 0 else 1 end")
            ->orderBy('cod_at')
            ->latest('id')
            ->take(50)
            ->get();

        $offers = Offer::query()
            ->when(
                $incoming,
                fn (Builder $query) => $query->whereHas('product', fn (Builder $query) => $query->where('user_id', $user->id)),
                fn (Builder $query) => $query->where('buyer_id', $user->id),
            )
            ->with(['product.cover', 'product.seller', 'buyer', 'booking'])
            ->latest()
            ->take(50)
            ->get();

        return view('inbox.index', [
            'tab' => $tab,
            'activeBookings' => $bookings->filter->isActive()->values(),
            'pastBookings' => $bookings->reject->isActive()->values(),
            'offers' => $offers,
        ]);
    }
}
