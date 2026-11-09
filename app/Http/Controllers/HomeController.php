<?php

namespace App\Http\Controllers;

use App\Enums\AdPlacement;
use App\Enums\ProductStatus;
use App\Enums\ReviewDirection;
use App\Enums\UserRole;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda (F5): banner iklan, kategori, Barang Unggulan, barang terbaru,
 * lapak terbaik, testimoni, dan pencarian populer.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->hasAny(['q', 'kategori'])) {
            return redirect()->route('catalog', $request->only(['q', 'kategori']));
        }

        $visibleProducts = fn (Builder $query) => $query->where('status', ProductStatus::Approved);

        return view('home', [
            'ads' => Ad::serve(AdPlacement::HomeTop),
            'featured' => Product::query()
                ->visible()
                ->promoted()
                ->with(['cover', 'seller'])
                ->inRandomOrder()
                ->take((int) config('jualin.promotion_slots'))
                ->get()
                ->each(fn (Product $product) => $product->setAttribute('is_promoted', true)),
            'testimonials' => Review::query()
                ->where('direction', ReviewDirection::BuyerToSeller)
                ->where('rating', '>=', 4)
                ->whereNotNull('comment')
                ->whereHas('reviewer', fn (Builder $query) => $query->where('is_active', true))
                ->with(['reviewer', 'reviewee'])
                ->latest()
                ->take(3)
                ->get(),
            'categories' => Category::query()
                ->withCount(['products' => fn (Builder $query) => $query->visible()])
                ->orderBy('id')
                ->get(),
            'products' => Product::query()
                ->visible()
                ->withPromotedFlag()
                ->with(['cover', 'seller'])
                ->latest()
                ->take(12)
                ->get(),
            'topSellers' => User::query()
                ->where('role', UserRole::Mahasiswa)
                ->where('is_active', true)
                ->whereHas('products', $visibleProducts)
                ->withCount(['products as active_products_count' => $visibleProducts, 'sellerReviews'])
                ->withAvg('sellerReviews', 'rating')
                ->orderByDesc('seller_reviews_avg_rating')
                ->orderByDesc('active_products_count')
                ->take(4)
                ->get(),
            'popularSearches' => SearchLog::query()->orderByDesc('count')->take(8)->pluck('term'),
        ]);
    }
}
