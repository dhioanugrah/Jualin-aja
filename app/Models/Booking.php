<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CodArea;
use App\Enums\ReviewDirection;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'code', 'product_id', 'buyer_id', 'seller_id', 'offer_id', 'agreed_price', 'cod_at',
    'cod_location_id', 'cod_area', 'note', 'status', 'handover_code', 'respond_by',
])]
#[Hidden(['handover_code'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'cod_area' => CodArea::class,
            'agreed_price' => 'integer',
            'cod_at' => 'datetime',
            'respond_by' => 'datetime',
            'accepted_at' => 'datetime',
            'buyer_confirmed_at' => 'datetime',
            'seller_confirmed_at' => 'datetime',
            'reminded_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_show_reported_at' => 'datetime',
            'dispute_resolved_at' => 'datetime',
            'handover_code' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            $booking->code ??= self::generateCode();
            $booking->handover_code ??= self::handoverCodeFor($booking->code);
        });
    }

    /**
     * Kode booking unik, misalnya JAITK-7Q2KD.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'JAITK-'.Str::upper(Str::random(5));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Kode serah terima 4 digit, diturunkan dari kode booking dan APP_KEY.
     * Database hanya menyimpan hash-nya; pembeli bisa melihatnya kapan saja
     * tanpa kode itu pernah tersimpan sebagai teks.
     */
    public static function handoverCodeFor(string $bookingCode): string
    {
        $hash = hash_hmac('sha256', 'handover|'.$bookingCode, (string) config('app.key'));

        return str_pad((string) (hexdec(substr($hash, 0, 8)) % 10000), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Booking yang masih berjalan: Menunggu atau Diterima.
     *
     * @param  Builder<Booking>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [BookingStatus::Pending, BookingStatus::Accepted]);
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
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo<CodLocation, $this>
     */
    public function codLocation(): BelongsTo
    {
        return $this->belongsTo(CodLocation::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Violation, $this>
     */
    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [BookingStatus::Pending, BookingStatus::Accepted], true);
    }

    public function isBuyer(?User $user): bool
    {
        return $user !== null && $user->id === $this->buyer_id;
    }

    public function isSeller(?User $user): bool
    {
        return $user !== null && $user->id === $this->seller_id;
    }

    public function involves(?User $user): bool
    {
        return $this->isBuyer($user) || $this->isSeller($user);
    }

    public function counterpartIdOf(User $user): int
    {
        return $this->isBuyer($user) ? $this->seller_id : $this->buyer_id;
    }

    public function confirmOpensAt(): Carbon
    {
        return $this->cod_at->copy()->subHours((int) config('jualin.confirm_opens_hours'));
    }

    public function confirmDeadline(): Carbon
    {
        return $this->cod_at->copy()->subHours((int) config('jualin.confirm_deadline_hours'));
    }

    /**
     * Konfirmasi kehadiran hanya bisa dilakukan mulai H-3 jam sampai jadwal COD.
     */
    public function isInConfirmWindow(): bool
    {
        return $this->status === BookingStatus::Accepted
            && now()->between($this->confirmOpensAt(), $this->cod_at);
    }

    public function hasConfirmed(User $user): bool
    {
        return $this->isBuyer($user) ? $this->buyer_confirmed_at !== null : $this->seller_confirmed_at !== null;
    }

    /**
     * Penjual boleh memasukkan kode sejak jendela konfirmasi dibuka (bisa bertemu lebih awal).
     */
    public function canEnterHandoverCode(): bool
    {
        return $this->status === BookingStatus::Accepted && now()->gte($this->confirmOpensAt());
    }

    public function isPastSchedule(): bool
    {
        return now()->gte($this->cod_at);
    }

    /**
     * Batas waktu untuk membantah laporan "tidak datang".
     */
    public function disputeDeadline(): ?Carbon
    {
        return $this->no_show_reported_at?->copy()->addHours((int) config('jualin.dispute_hours'));
    }

    public function canBeDisputedBy(User $user): bool
    {
        return $this->status === BookingStatus::Cancelled
            && $this->no_show_reported_by !== null
            && $this->no_show_reported_by !== $user->id
            && $this->involves($user)
            && now()->lt($this->disputeDeadline());
    }

    /**
     * Arah ulasan yang diberikan pengguna ini untuk booking ini.
     */
    public function reviewDirectionFor(User $user): ReviewDirection
    {
        return $this->isBuyer($user) ? ReviewDirection::BuyerToSeller : ReviewDirection::SellerToBuyer;
    }

    /**
     * Rating dua arah (F11): kedua pihak booking Selesai bisa memberi satu ulasan
     * sampai batas hari tertentu setelah selesai.
     */
    public function canBeReviewedBy(User $user): bool
    {
        if ($this->status !== BookingStatus::Completed || ! $this->involves($user)) {
            return false;
        }

        $completedAt = $this->completed_at ?? $this->updated_at;

        if (now()->gt($completedAt->copy()->addDays((int) config('jualin.review_window_days')))) {
            return false;
        }

        $direction = $this->reviewDirectionFor($user);

        return $this->relationLoaded('reviews')
            ? ! $this->reviews->contains('direction', $direction)
            : ! $this->reviews()->where('direction', $direction)->exists();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => $this->agreed_price === 0
            ? 'Gratis'
            : 'Rp '.number_format($this->agreed_price, 0, ',', '.'));
    }
}
