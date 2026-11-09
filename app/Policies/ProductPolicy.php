<?php

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Barang yang belum disetujui hanya bisa dilihat pemilik dan admin.
     */
    public function view(?User $user, Product $product): bool
    {
        if ($product->status === ProductStatus::Approved && $product->seller->is_active) {
            return true;
        }

        return $user !== null && ($user->id === $product->user_id || $user->isAdmin());
    }

    public function create(User $user): bool
    {
        return $user->canSell();
    }

    /**
     * Barang yang sudah terjual tidak bisa diedit lagi.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id && ! in_array($product->status, [ProductStatus::Sold, ProductStatus::Hidden], true);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    /**
     * Hanya barang yang sudah tayang yang bisa ditandai terjual.
     */
    public function markSold(User $user, Product $product): bool
    {
        return $user->id === $product->user_id && $product->status === ProductStatus::Approved;
    }

    /**
     * Hubungi via WA: akun terverifikasi, bukan pemilik barang, dan barang masih tersedia.
     */
    public function contact(User $user, Product $product): bool
    {
        return $user->hasVerifiedEmail()
            && ! $user->isAdmin()
            && $user->id !== $product->user_id
            && $product->isAvailable()
            && filled($product->seller->whatsapp);
    }

    public function moderate(User $user, Product $product): bool
    {
        return $user->isAdmin() && $product->status === ProductStatus::Pending;
    }
}
