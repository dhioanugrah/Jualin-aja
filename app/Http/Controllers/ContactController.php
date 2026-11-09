<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Hubungi penjual via WhatsApp (F7, Proposal 3.3 lapis 2).
 * Nomor penjual tidak pernah tampil sebagai teks: route ini mencatat kontak,
 * menerapkan batas kontak baru per hari, lalu meneruskan ke wa.me.
 */
class ContactController extends Controller
{
    public function __invoke(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();
        $product->loadMissing('seller');

        Gate::authorize('contact', $product);

        $alreadyContacted = $user->contactLogs()->where('product_id', $product->id)->exists();

        if (! $alreadyContacted) {
            $limit = (int) config('jualin.contact_daily_limit');
            $contactedToday = $user->contactLogs()
                ->where('created_at', '>=', now()->startOfDay())
                ->distinct()
                ->count('product_id');

            if ($contactedToday >= $limit) {
                return back()->withErrors([
                    'contact' => "Batas {$limit} kontak WA ke barang baru per hari sudah tercapai. Coba lagi besok, atau lanjutkan barang yang sudah kamu hubungi.",
                ]);
            }
        }

        $user->contactLogs()->create(['product_id' => $product->id, 'channel' => 'whatsapp']);

        $message = "Halo {$product->seller->name}, saya {$user->name} dari Jualin Aja ITK. Saya tertarik dengan \"{$product->name}\" ({$product->formatted_price}). Apakah masih tersedia?\n".route('products.show', $product);

        return redirect()->away('https://wa.me/'.User::normalizeWhatsapp((string) $product->seller->whatsapp).'?text='.rawurlencode($message));
    }
}
