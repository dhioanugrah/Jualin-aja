<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Promosikan barang ke slot Barang Unggulan (F15).
 */
class PromotionController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $response = Gate::inspect('create', [Promotion::class, $product]);

        if ($response->denied()) {
            return back()->withErrors(['promotion' => $response->message()]);
        }

        $days = (int) config('jualin.promotion_days');

        $product->promotions()->create([
            'user_id' => $request->user()->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
        ]);

        return back()->with('toast', "\"{$product->name}\" jadi Barang Unggulan selama {$days} hari.");
    }
}
