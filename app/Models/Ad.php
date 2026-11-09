<?php

namespace App\Models;

use App\Enums\AdPlacement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Banner iklan yang dikelola admin (F15). Pembayaran dicatat di luar sistem.
 */
#[Fillable(['title', 'advertiser', 'image', 'url', 'placement', 'starts_at', 'ends_at', 'is_active'])]
class Ad extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'placement' => AdPlacement::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /**
     * Iklan aktif yang sedang dalam masa tayang.
     *
     * @param  Builder<Ad>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    /**
     * Ambil iklan yang tayang di satu posisi lalu catat tayangannya.
     *
     * @return Collection<int, Ad>
     */
    public static function serve(AdPlacement $placement, int $limit = 5): Collection
    {
        $ads = self::query()->live()->where('placement', $placement)->inRandomOrder()->take($limit)->get();

        if ($ads->isNotEmpty()) {
            self::query()->whereKey($ads->modelKeys())->increment('impressions');
        }

        return $ads;
    }

    public function isLive(): bool
    {
        return $this->is_active && now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->image));
    }

    /**
     * Rasio klik per tayang dalam persen.
     *
     * @return Attribute<float, never>
     */
    protected function ctr(): Attribute
    {
        return Attribute::get(fn (): float => $this->impressions > 0 ? round($this->clicks / $this->impressions * 100, 1) : 0.0);
    }
}
