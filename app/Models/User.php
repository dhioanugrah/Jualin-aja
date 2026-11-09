<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\ProductStatus;
use App\Enums\ReviewDirection;
use App\Enums\UserMode;
use App\Enums\UserRole;
use App\Enums\ViolationStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'nim', 'email', 'whatsapp', 'avatar', 'prodi', 'angkatan', 'password', 'active_mode'])]
#[Hidden(['password', 'remember_token', 'whatsapp'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'mahasiswa',
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active_mode' => UserMode::class,
            'is_active' => 'boolean',
            'booking_blocked_until' => 'datetime',
            'angkatan' => 'integer',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'buyer_id');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Booking::class, 'buyer_id');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Booking::class, 'seller_id');
    }

    /**
     * Ulasan yang diterima pengguna ini sebagai penjual.
     *
     * @return HasMany<Review, $this>
     */
    public function sellerReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id')
            ->where('direction', ReviewDirection::BuyerToSeller);
    }

    /**
     * Ulasan yang diterima pengguna ini sebagai pembeli (rating dua arah, F11).
     *
     * @return HasMany<Review, $this>
     */
    public function buyerReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id')
            ->where('direction', ReviewDirection::SellerToBuyer);
    }

    /**
     * Rata-rata dan jumlah ulasan untuk satu peran.
     *
     * @return array{average: float|null, count: int}
     */
    public function ratingAs(UserMode $role): array
    {
        $reviews = $role === UserMode::Seller ? $this->sellerReviews() : $this->buyerReviews();
        $stats = $reviews->toBase()->selectRaw('avg(rating) as average, count(*) as total')->first();

        return [
            'average' => $stats->average !== null ? (float) $stats->average : null,
            'count' => (int) $stats->total,
        ];
    }

    /**
     * @param  array{average: float|null, count: int}  $rating
     */
    public static function isTopSeller(array $rating): bool
    {
        return $rating['count'] >= (int) config('jualin.top_seller_min_reviews')
            && $rating['average'] >= (float) config('jualin.top_seller_min_rating');
    }

    /**
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Sisa kuota promosi gratis dalam 7 hari terakhir.
     */
    public function promotionQuotaLeft(): int
    {
        $used = $this->promotions()->where('starts_at', '>', now()->subDays(7))->count();

        return max(0, (int) config('jualin.promotion_weekly_quota') - $used);
    }

    /**
     * Kapan kuota promosi berikutnya tersedia (null jika masih ada kuota).
     */
    public function nextPromotionAt(): ?Carbon
    {
        if ($this->promotionQuotaLeft() > 0) {
            return null;
        }

        return $this->promotions()->where('starts_at', '>', now()->subDays(7))->oldest('starts_at')->first()?->starts_at->copy()->addDays(7);
    }

    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /**
     * @return HasMany<Violation, $this>
     */
    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    /**
     * Ubah 0812…, +62 812…, atau 62812… menjadi format 62812… untuk wa.me.
     */
    public static function normalizeWhatsapp(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        return match (true) {
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }

    /**
     * @return HasMany<ContactLog, $this>
     */
    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class);
    }

    /**
     * ID barang favorit, dihitung sekali per request untuk tombol hati di kartu barang.
     *
     * @return array<int, int>
     */
    public function favoriteProductIds(): array
    {
        return once(fn (): array => $this->favorites()->pluck('products.id')->all());
    }

    public function hasFavorited(Product $product): bool
    {
        return in_array($product->id, $this->favoriteProductIds(), true);
    }

    public function isBookingBlocked(): bool
    {
        return $this->booking_blocked_until !== null && $this->booking_blocked_until->isFuture();
    }

    public function activeBookingsCount(): int
    {
        return $this->purchases()->active()->count();
    }

    /**
     * Pelanggaran aktif dalam jendela sanksi (30 hari terakhir).
     */
    public function recentViolationsCount(): int
    {
        return $this->violations()
            ->where('status', ViolationStatus::Active)
            ->where('created_at', '>=', now()->subDays((int) config('jualin.violation_window_days')))
            ->count();
    }

    /**
     * Skor keandalan (Proposal 3.3 lapis 7): persentase booking yang selesai dari semua booking
     * yang diterima, dihitung per peran. Booking yang gagal karena pihak lain tidak dihitung
     * sebagai kegagalan pengguna ini; yang dihitung hanya pelanggaran miliknya sendiri.
     *
     * @return array{percent: int, total: int}|null null jika belum cukup transaksi ("baru")
     */
    public function reliability(UserMode $role): ?array
    {
        $column = $role === UserMode::Buyer ? 'buyer_id' : 'seller_id';

        $completed = Booking::query()
            ->where($column, $this->id)
            ->where('status', BookingStatus::Completed)
            ->count();

        $failed = Booking::query()
            ->where($column, $this->id)
            ->whereHas('violations', fn (Builder $query) => $query
                ->where('user_id', $this->id)
                ->where('status', '!=', ViolationStatus::Revoked))
            ->count();

        $total = $completed + $failed;

        if ($total < (int) config('jualin.reliability_min_bookings')) {
            return null;
        }

        return ['percent' => (int) round($completed / $total * 100), 'total' => $total];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSellerMode(): bool
    {
        return $this->active_mode === UserMode::Seller;
    }

    /**
     * Penjual harus terverifikasi dan sudah mengisi nomor WA.
     */
    public function canSell(): bool
    {
        return $this->hasVerifiedEmail() && filled($this->whatsapp);
    }

    public function activeProductsCount(): int
    {
        return $this->products()->where('status', ProductStatus::Approved)->count();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar ? Storage::disk('public')->url($this->avatar) : null);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $words = preg_split('/\s+/', trim($this->name)) ?: [];

            return mb_strtoupper(collect($words)->take(2)->map(fn (string $word): string => mb_substr($word, 0, 1))->implode(''));
        });
    }
}
