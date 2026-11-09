<?php

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\Artisan;

test('a seller can promote an approved product once per week for three days', function () {
    $this->freezeSecond();
    $product = Product::factory()->approved()->create();
    $other = Product::factory()->approved()->for($product->seller, 'seller')->create();

    $this->actingAs($product->seller)->post("/barang/{$product->id}/promosi")->assertSessionHasNoErrors();

    $promotion = Promotion::query()->sole();
    expect($promotion->ends_at->equalTo(now()->addDays(3)))->toBeTrue()
        ->and($product->isPromoted())->toBeTrue();

    $this->actingAs($product->seller)->post("/barang/{$other->id}/promosi")->assertSessionHasErrors('promotion');
    expect(Promotion::query()->count())->toBe(1);

    $this->travel(7 * 24 * 60 * 60 + 1)->seconds();
    $this->actingAs($product->seller)->post("/barang/{$other->id}/promosi")->assertSessionHasNoErrors();
    expect(Promotion::query()->count())->toBe(2);
});

test('only the owner can promote, and only approved products', function () {
    $pending = Product::factory()->create();
    $approved = Product::factory()->approved()->create();
    $stranger = Product::factory()->approved()->create()->seller;

    $this->actingAs($pending->seller)->post("/barang/{$pending->id}/promosi")->assertSessionHasErrors('promotion');
    $this->actingAs($stranger)->post("/barang/{$approved->id}/promosi")->assertSessionHasErrors('promotion');

    expect(Promotion::query()->count())->toBe(0);
});

test('promoted products appear as featured on the home page and above their category in the catalog', function () {
    $promoted = Product::factory()->approved()->create(['name' => 'Kalkulator Casio Unggulan']);
    $promoted->promotions()->create(['user_id' => $promoted->user_id, 'starts_at' => now(), 'ends_at' => now()->addDays(3)]);
    $expired = Product::factory()->approved()->create(['name' => 'Promosi Lama Sekali']);
    $expired->promotions()->create(['user_id' => $expired->user_id, 'starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(2)]);

    $this->get('/')->assertSee('Barang Unggulan')->assertSeeInOrder(['Barang Unggulan', 'Kalkulator Casio Unggulan', 'Barang terbaru']);
    $this->get('/katalog?kategori='.$promoted->category->slug)->assertSeeInOrder(['Unggulan di', 'Kalkulator Casio Unggulan']);

    expect(Product::query()->promoted()->pluck('id')->all())->toBe([$promoted->id]);
});

test('promotions:expire ends promotions of products that are no longer visible', function () {
    $sold = Product::factory()->sold()->create();
    $sold->promotions()->create(['user_id' => $sold->user_id, 'starts_at' => now(), 'ends_at' => now()->addDays(3)]);
    $live = Product::factory()->approved()->create();
    $live->promotions()->create(['user_id' => $live->user_id, 'starts_at' => now(), 'ends_at' => now()->addDays(3)]);

    Artisan::call('promotions:expire');

    expect(Promotion::query()->running()->pluck('product_id')->all())->toBe([$live->id]);
});
