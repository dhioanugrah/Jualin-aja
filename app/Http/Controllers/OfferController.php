<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Http\Requests\OfferRequest;
use App\Models\Offer;
use App\Models\Product;
use App\Notifications\TransactionUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Penawaran harga (F8). Satu penawaran aktif per pembeli per barang;
 * penawaran Diterima harus dijadikan booking dalam 24 jam.
 */
class OfferController extends Controller
{
    public function store(OfferRequest $request, Product $product): RedirectResponse
    {
        $buyer = $request->user();

        if ($product->offers()->active()->where('buyer_id', $buyer->id)->exists()) {
            return back()->withErrors(['price' => 'Kamu masih punya penawaran aktif untuk barang ini.']);
        }

        $offer = $product->offers()->create([
            'buyer_id' => $buyer->id,
            'price' => $request->integer('price'),
            'message' => $request->validated('message'),
            'status' => OfferStatus::Pending,
        ]);

        $product->seller->notify(new TransactionUpdate(
            'Penawaran baru',
            "{$buyer->name} menawar \"{$product->name}\" seharga {$offer->formatted_price}.",
            route('inbox', ['tab' => 'masuk']),
        ));

        return back()->with('toast', 'Penawaran terkirim. Kamu akan diberi tahu saat penjual merespons.');
    }

    public function accept(Request $request, Offer $offer): RedirectResponse
    {
        Gate::authorize('respond', $offer);

        if (! $offer->product->isAvailable()) {
            return back()->withErrors(['offer' => 'Barang ini sudah tidak tersedia.']);
        }

        $hours = (int) config('jualin.offer_booking_window_hours');
        $offer->update(['status' => OfferStatus::Accepted, 'expires_at' => now()->addHours($hours)]);

        $offer->buyer->notify(new TransactionUpdate(
            'Penawaran diterima',
            "Tawaran {$offer->formatted_price} untuk \"{$offer->product->name}\" diterima. Ajukan booking sebelum {$offer->expires_at->translatedFormat('j M H:i')}.",
            route('bookings.create', ['product' => $offer->product, 'penawaran' => $offer->id]),
            'success',
        ));

        return back()->with('toast', "Penawaran diterima. Pembeli punya {$hours} jam untuk mengajukan booking.");
    }

    public function reject(Request $request, Offer $offer): RedirectResponse
    {
        Gate::authorize('respond', $offer);

        $offer->update(['status' => OfferStatus::Rejected]);

        $offer->buyer->notify(new TransactionUpdate(
            'Penawaran ditolak',
            "Tawaran {$offer->formatted_price} untuk \"{$offer->product->name}\" ditolak penjual.",
            route('products.show', $offer->product),
            'warning',
        ));

        return back()->with('toast', 'Penawaran ditolak.');
    }

    public function cancel(Request $request, Offer $offer): RedirectResponse
    {
        Gate::authorize('cancel', $offer);

        $offer->update(['status' => OfferStatus::Cancelled]);

        return back()->with('toast', 'Penawaran dibatalkan.');
    }
}
