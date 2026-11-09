<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Barang Saya dengan tab status (F4).
 */
class MyProductController extends Controller
{
    public function index(Request $request): View
    {
        $status = ProductStatus::tryFrom($request->string('status')->toString());
        $user = $request->user();

        $counts = $user->products()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $products = $user->products()
            ->withPromotedFlag()
            ->with(['cover', 'category'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('my-products.index', [
            'products' => $products,
            'activeStatus' => $status,
            'counts' => $counts,
            'total' => $counts->sum(),
            'promotionQuota' => $user->promotionQuotaLeft(),
            'nextPromotionAt' => $user->nextPromotionAt(),
        ]);
    }
}
