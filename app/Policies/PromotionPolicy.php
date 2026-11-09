<?php

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Promosi gratis (F15): pemilik barang Disetujui, kuota 1 per 7 hari, satu promosi berjalan per barang.
 */
class PromotionPolicy
{
    public function create(User $user, Product $product): Response
    {
        if ($user->id !== $product->user_id || $product->status !== ProductStatus::Approved) {
            return Response::deny('Hanya barang milikmu yang sudah Disetujui yang bisa dipromosikan.');
        }

        if ($product->promotions()->running()->exists()) {
            return Response::deny('Barang ini sedang dipromosikan.');
        }

        if ($user->promotionQuotaLeft() < 1) {
            return Response::deny('Kuota promosi minggu ini sudah terpakai. Kuota berikutnya tersedia '.$user->nextPromotionAt()?->translatedFormat('j M H:i').'.');
        }

        return Response::allow();
    }
}
