<?php

namespace App\Http\Controllers;

use App\Enums\CodArea;
use App\Enums\HandoverMethod;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserMode;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Jual, edit, hapus, dan tandai terjual (F2), plus detail barang (F7).
 */
class ProductController extends Controller
{
    public function show(Request $request, Product $product): View
    {
        $product->load(['images', 'category', 'seller']);

        Gate::authorize('view', $product);

        if ($request->user()?->id !== $product->user_id) {
            $product->increment('views');
        }

        $user = $request->user();

        return view('products.show', [
            'product' => $product,
            'sellerRating' => $product->seller->ratingAs(UserMode::Seller),
            'isPromoted' => $product->isPromoted(),
            'sellerReliability' => $product->seller->reliability(UserMode::Seller),
            'myOffer' => $user ? $product->offers()->active()->where('buyer_id', $user->id)->latest()->first() : null,
            'myBooking' => $user ? $product->bookings()->active()->where('buyer_id', $user->id)->latest()->first() : null,
            'moreFromSeller' => Product::query()
                ->visible()
                ->whereBelongsTo($product->seller, 'seller')
                ->whereKeyNot($product->id)
                ->with(['cover', 'seller'])
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create', $this->formOptions() + ['product' => new Product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $product = DB::transaction(function () use ($request): Product {
            $product = $request->user()->products()->create($request->safe()->except(['photos', 'photo_order']));
            $this->syncPhotos($product, $request);

            return $product;
        });

        return redirect()->route('my-products.index', ['status' => ProductStatus::Pending->value])
            ->with('toast', "\"{$product->name}\" terkirim dan menunggu moderasi admin.");
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        $product->load('images');

        return view('products.edit', $this->formOptions() + ['product' => $product]);
    }

    /**
     * Setiap perubahan mengembalikan barang ke antrean moderasi.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        DB::transaction(function () use ($request, $product): void {
            $product->fill($request->safe()->except(['photos', 'photo_order']));
            $product->status = ProductStatus::Pending;
            $product->moderation_note = null;
            $product->save();

            $this->syncPhotos($product, $request);
        });

        return redirect()->route('my-products.index', ['status' => ProductStatus::Pending->value])
            ->with('toast', 'Perubahan tersimpan. Barang kembali menunggu moderasi.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $paths = $product->images()->pluck('path')->all();
        $product->delete();
        Storage::disk('public')->delete($paths);

        return redirect()->route('my-products.index')->with('toast', 'Barang dihapus.');
    }

    public function markSold(Product $product): RedirectResponse
    {
        Gate::authorize('markSold', $product);

        $product->forceFill(['status' => ProductStatus::Sold, 'sold_at' => now()])->save();

        return back()->with('toast', "\"{$product->name}\" ditandai terjual.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::query()->orderBy('id')->get(),
            'conditions' => ProductCondition::cases(),
            'handoverMethods' => HandoverMethod::cases(),
            'codAreas' => CodArea::cases(),
        ];
    }

    /**
     * Simpan foto baru, pertahankan foto lama yang dipilih, hapus sisanya,
     * lalu urutkan sesuai photo_order ("e:{id}" foto lama, "n:{index}" foto baru).
     */
    private function syncPhotos(Product $product, ProductRequest $request): void
    {
        $keptIds = $request->keptImageIds();
        $newFiles = array_values($request->file('photos', []));

        $removed = $product->images()->whereKeyNot($keptIds)->get();
        Storage::disk('public')->delete($removed->pluck('path')->all());
        $product->images()->whereKey($removed->modelKeys())->delete();

        $existing = $product->images()->get()->keyBy('id');
        $order = $request->input('photo_order', []);

        if ($order === []) {
            $order = [
                ...$existing->keys()->map(fn (int $id): string => "e:{$id}"),
                ...array_map(fn (int $index): string => "n:{$index}", array_keys($newFiles)),
            ];
        }

        $position = 0;
        $usedNew = [];

        foreach ($order as $token) {
            [$type, $key] = explode(':', $token);
            $key = (int) $key;

            if ($type === 'e' && $existing->has($key)) {
                $existing[$key]->update(['order' => $position++]);
            }

            if ($type === 'n' && isset($newFiles[$key]) && ! isset($usedNew[$key])) {
                $this->storePhoto($product, $newFiles[$key], $position++);
                $usedNew[$key] = true;
            }
        }

        foreach ($newFiles as $index => $file) {
            if (! isset($usedNew[$index])) {
                $this->storePhoto($product, $file, $position++);
            }
        }
    }

    private function storePhoto(Product $product, UploadedFile $file, int $order): void
    {
        $product->images()->create([
            'path' => $file->store('products', 'public'),
            'order' => $order,
        ]);
    }
}
