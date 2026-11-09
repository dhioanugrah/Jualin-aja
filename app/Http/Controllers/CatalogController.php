<?php

namespace App\Http\Controllers;

use App\Enums\AdPlacement;
use App\Enums\CodArea;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Katalog (F6): tab Barang dengan filter dan urutan, tab Lapak berisi daftar penjual.
 * Semua filter disimpan di query string supaya bisa dibagikan dan tetap saat kembali.
 */
class CatalogController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const SORTS = [
        'terbaru' => 'Terbaru',
        'termurah' => 'Termurah',
        'termahal' => 'Termahal',
    ];

    /**
     * @var array<string, string>
     */
    public const SELLER_SORTS = [
        'rating' => 'Rating tertinggi',
        'barang' => 'Barang terbanyak',
    ];

    public function __invoke(Request $request): View
    {
        $tab = $request->query('tab') === 'lapak' ? 'lapak' : 'barang';
        $search = Str::limit(trim($request->string('q')->toString()), 80, '');
        $categories = Category::query()->orderBy('id')->get();

        if ($search !== '' && ! $request->filled('page')) {
            $this->logSearch($search);
        }

        if ($tab === 'lapak') {
            return view('catalog.index', [
                'tab' => $tab,
                'search' => $search,
                'categories' => $categories,
                'sellers' => $this->sellers($request, $search),
                'sort' => array_key_exists($request->query('urut'), self::SELLER_SORTS) ? $request->query('urut') : 'rating',
            ]);
        }

        $filters = $this->productFilters($request, $categories);
        $products = $this->products($filters, $search);

        return view('catalog.index', [
            'featured' => $products->onFirstPage() ? $this->featured($filters, $search) : collect(),
            'inlineAd' => $products->count() > 8 ? Ad::serve(AdPlacement::CatalogInline, 1)->first() : null,
            'tab' => $tab,
            'search' => $search,
            'categories' => $categories,
            'filters' => $filters,
            'products' => $products,
            'conditions' => ProductCondition::cases(),
            'codAreas' => CodArea::cases(),
        ]);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array{category: Category|null, conditions: array<int, ProductCondition>, min: int|null, max: int|null, area: CodArea|null, sort: string}
     */
    private function productFilters(Request $request, $categories): array
    {
        $min = $request->integer('harga_min') ?: null;
        $max = $request->integer('harga_max') ?: null;

        return [
            'category' => $categories->firstWhere('slug', $request->string('kategori')->toString()),
            'conditions' => collect((array) $request->query('kondisi', []))
                ->map(fn (mixed $value): ?ProductCondition => is_string($value) ? ProductCondition::tryFrom($value) : null)
                ->filter()
                ->values()
                ->all(),
            'min' => $min !== null ? max(0, $min) : null,
            'max' => $max !== null ? max(0, $max) : null,
            'area' => is_string($request->query('area')) ? CodArea::tryFrom($request->query('area')) : null,
            'sort' => array_key_exists($request->query('urut'), self::SORTS) ? $request->query('urut') : 'terbaru',
        ];
    }

    /**
     * @param  array{category: Category|null, conditions: array<int, ProductCondition>, min: int|null, max: int|null, area: CodArea|null, sort: string}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    private function products(array $filters, string $search)
    {
        return Product::query()
            ->visible()
            ->withPromotedFlag()
            ->with(['cover', 'seller'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['category'], fn (Builder $query, Category $category) => $query->whereBelongsTo($category))
            ->when($filters['conditions'] !== [], fn (Builder $query) => $query->whereIn('condition', $filters['conditions']))
            ->when($filters['min'] !== null, fn (Builder $query) => $query->where('price', '>=', $filters['min']))
            ->when($filters['max'] !== null, fn (Builder $query) => $query->where('price', '<=', $filters['max']))
            ->when($filters['area'], fn (Builder $query, CodArea $area) => $query->whereJsonContains('cod_areas', $area->value))
            ->when($filters['sort'] === 'termurah', fn (Builder $query) => $query->orderBy('price')->latest('id'))
            ->when($filters['sort'] === 'termahal', fn (Builder $query) => $query->orderByDesc('price')->latest('id'))
            ->when($filters['sort'] === 'terbaru', fn (Builder $query) => $query->latest()->latest('id'))
            ->paginate((int) config('jualin.catalog_per_page'))
            ->withQueryString();
    }

    /**
     * Barang Unggulan teratas di katalog kategorinya (F15): mengikuti kategori dan kata kunci yang sedang dipilih.
     *
     * @param  array{category: Category|null, conditions: array<int, ProductCondition>, min: int|null, max: int|null, area: CodArea|null, sort: string}  $filters
     * @return Collection<int, Product>
     */
    private function featured(array $filters, string $search)
    {
        return Product::query()
            ->visible()
            ->promoted()
            ->with(['cover', 'seller'])
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['category'], fn (Builder $query, Category $category) => $query->whereBelongsTo($category))
            ->inRandomOrder()
            ->take(4)
            ->get()
            ->each(fn (Product $product) => $product->setAttribute('is_promoted', true));
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    private function sellers(Request $request, string $search)
    {
        $visibleProducts = fn (Builder $query) => $query->where('status', ProductStatus::Approved);

        return User::query()
            ->where('role', UserRole::Mahasiswa)
            ->where('is_active', true)
            ->whereHas('products', $visibleProducts)
            ->withCount(['products as active_products_count' => $visibleProducts, 'sellerReviews'])
            ->withAvg('sellerReviews', 'rating')
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when(
                $request->query('urut') === 'barang',
                fn (Builder $query) => $query->orderByDesc('active_products_count'),
                fn (Builder $query) => $query->orderByDesc('seller_reviews_avg_rating')->orderByDesc('active_products_count'),
            )
            ->orderBy('name')
            ->paginate((int) config('jualin.catalog_per_page'))
            ->withQueryString();
    }

    private function logSearch(string $term): void
    {
        $term = Str::lower($term);
        $log = SearchLog::query()->firstOrCreate(['term' => $term], ['count' => 0]);
        $log->increment('count');
    }
}
