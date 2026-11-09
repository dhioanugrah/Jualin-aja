<?php

namespace App\Models;

use App\Enums\CodArea;
use App\Enums\HandoverMethod;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'name', 'description', 'condition', 'price', 'is_donation',
    'handover_method', 'cod_areas', 'location',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'condition' => ProductCondition::class,
            'status' => ProductStatus::class,
            'handover_method' => HandoverMethod::class,
            'cod_areas' => AsEnumCollection::of(CodArea::class),
            'is_donation' => 'boolean',
            'price' => 'integer',
            'views' => 'integer',
            'sold_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->slug ??= Str::slug(Str::limit($product->name, 60, '')).'-'.Str::lower(Str::random(6));
        });

        static::saving(function (Product $product): void {
            if ($product->is_donation) {
                $product->price = 0;
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(ProductImage::class)->oldestOfMany('order');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isPromoted(): bool
    {
        return (bool) ($this->attributes['is_promoted'] ?? $this->promotions()->running()->exists());
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::Approved && $this->seller->is_active;
    }

    public function acceptsCodArea(CodArea $area): bool
    {
        return $this->cod_areas->contains($area);
    }

    /**
     * Barang yang tampil di katalog publik: disetujui, belum terjual, dan penjualnya aktif.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('status', ProductStatus::Approved)
            ->whereHas('seller', fn (Builder $seller) => $seller->where('is_active', true));
    }

    /**
     * Tambahkan kolom is_promoted supaya kartu barang bisa menampilkan label "Unggulan" tanpa query tambahan.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withPromotedFlag(Builder $query): void
    {
        $query->withExists(['promotions as is_promoted' => fn (Builder $promotion) => $promotion->running()]);
    }

    /**
     * Barang yang sedang dipromosikan.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function promoted(Builder $query): void
    {
        $query->whereHas('promotions', fn (Builder $promotion) => $promotion->running());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_donation || $this->price === 0
            ? 'Gratis'
            : 'Rp '.number_format($this->price, 0, ',', '.'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover ? Storage::disk('public')->url($this->cover->path) : null);
    }

    /**
     * Label area COD, misalnya "Dalam ITK · Sekitar ITK".
     *
     * @return Attribute<string, never>
     */
    protected function codAreasLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->cod_areas
            ->map(fn (CodArea $area): string => $area->label())
            ->implode(' · '));
    }
}
