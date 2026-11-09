<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ModerationRequest;
use App\Models\Product;
use App\Notifications\ProductModerated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Antrean moderasi barang (F3).
 */
class ModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.moderation.index', [
            'products' => Product::query()
                ->where('status', ProductStatus::Pending)
                ->with(['images', 'seller', 'category'])
                ->oldest('updated_at')
                ->paginate(10),
        ]);
    }

    public function update(ModerationRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('moderate', $product);

        $decision = ProductStatus::from($request->validated('decision'));

        $product->forceFill([
            'status' => $decision,
            'moderation_note' => $decision === ProductStatus::NeedsRevision ? $request->validated('note') : null,
        ])->save();

        $product->loadMissing('seller')->seller->notify(new ProductModerated($product));

        return back()->with('toast', $decision === ProductStatus::Approved
            ? "\"{$product->name}\" disetujui dan tayang."
            : "\"{$product->name}\" dikembalikan ke penjual untuk diperbaiki.");
    }
}
