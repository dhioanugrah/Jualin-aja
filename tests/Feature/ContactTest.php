<?php

use App\Models\ContactLog;
use App\Models\Product;
use App\Models\User;

test('contacting a seller logs the contact and redirects to wa.me', function () {
    $seller = User::factory()->seller()->create(['whatsapp' => '6281211112222']);
    $product = Product::factory()->approved()->for($seller, 'seller')->create();
    $buyer = User::factory()->buyer()->create();

    $response = $this->actingAs($buyer)->post("/barang/{$product->id}/wa");

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://wa.me/6281211112222?text=');
    expect(ContactLog::query()->where('user_id', $buyer->id)->where('product_id', $product->id)->exists())->toBeTrue();
});

test('a buyer can contact at most 15 new products per day', function () {
    $this->travelTo(now()->setTime(9, 0));
    $buyer = User::factory()->buyer()->create();
    $products = Product::factory()->approved()->count(16)->create();

    foreach ($products->take(15) as $product) {
        $this->actingAs($buyer)->post("/barang/{$product->id}/wa")->assertRedirect();
        $this->travel(10)->seconds();
    }

    $this->actingAs($buyer)->post("/barang/{$products->last()->id}/wa")->assertSessionHasErrors('contact');

    // Barang yang sudah pernah dihubungi tetap bisa dihubungi lagi.
    $again = $this->actingAs($buyer)->post("/barang/{$products->first()->id}/wa");
    expect($again->headers->get('Location'))->toStartWith('https://wa.me/');
});

test('the daily contact limit resets the next day', function () {
    config(['jualin.contact_daily_limit' => 1]);
    $this->travelTo(now()->setTime(9, 0));
    $buyer = User::factory()->buyer()->create();
    [$first, $second] = Product::factory()->approved()->count(2)->create();

    $this->actingAs($buyer)->post("/barang/{$first->id}/wa");
    $this->actingAs($buyer)->post("/barang/{$second->id}/wa")->assertSessionHasErrors('contact');

    $this->travel(1)->days();
    $response = $this->actingAs($buyer)->post("/barang/{$second->id}/wa");
    expect($response->headers->get('Location'))->toStartWith('https://wa.me/');
});

test('sellers cannot contact themselves and unverified users cannot contact', function () {
    $product = Product::factory()->approved()->create();

    $this->actingAs($product->seller)->post("/barang/{$product->id}/wa")->assertForbidden();
    $this->actingAs(User::factory()->unverified()->create())->post("/barang/{$product->id}/wa")->assertRedirect(route('verification.notice'));
});

test('guests must log in before contacting', function () {
    $product = Product::factory()->approved()->create();

    $this->post("/barang/{$product->id}/wa")->assertRedirect(route('login'));
});
