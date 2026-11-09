<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['product_id', 'buyer_id', 'price', 'message', 'status', 'expires_at'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'price' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Penawaran yang masih berjalan: Menunggu, atau Diterima dan belum lewat batas 24 jam.
     *
     * @param  Builder<Offer>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('status', OfferStatus::Pending)
            ->orWhere(fn (Builder $query) => $query
                ->where('status', OfferStatus::Accepted)
                ->where('expires_at', '>', now())
                ->whereDoesntHave('booking')));
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return HasOne<Booking, $this>
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    /**
     * Penawaran Diterima yang belum kedaluwarsa dan belum dipakai bisa dijadikan booking.
     */
    public function isBookable(): bool
    {
        return $this->status === OfferStatus::Accepted
            && $this->expires_at?->isFuture()
            && ! $this->booking()->exists();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => 'Rp '.number_format($this->price, 0, ',', '.'));
    }
}
