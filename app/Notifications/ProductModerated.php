<?php

namespace App\Notifications;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Notifications\Notification;

/**
 * Hasil moderasi admin untuk penjual (F3).
 */
class ProductModerated extends Notification
{
    public function __construct(public Product $product) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $approved = $this->product->status === ProductStatus::Approved;

        return [
            'title' => $approved ? 'Barang kamu sudah tayang' : 'Barang kamu perlu diperbaiki',
            'body' => $approved
                ? "\"{$this->product->name}\" lolos moderasi dan sekarang tampil di katalog."
                : "\"{$this->product->name}\": {$this->product->moderation_note}",
            'url' => $approved
                ? route('products.show', $this->product)
                : route('products.edit', $this->product),
            'tone' => $approved ? 'success' : 'warning',
        ];
    }
}
