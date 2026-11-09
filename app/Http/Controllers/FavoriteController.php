<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tombol hati dan halaman Favorit (F12).
 */
class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        return view('favorites.index', [
            'products' => $request->user()->favorites()
                ->visible()
                ->with(['cover', 'seller'])
                ->latest('favorites.created_at')
                ->paginate((int) config('jualin.catalog_per_page')),
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('view', $product);

        $result = $request->user()->favorites()->toggle($product->id);
        $added = $result['attached'] !== [];

        return back()->with('toast', $added ? 'Disimpan ke Favorit.' : 'Dihapus dari Favorit.');
    }
}
