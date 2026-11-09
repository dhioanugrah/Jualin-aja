<?php

use App\Models\Product;
use App\Models\User;

test('users can toggle a product in their favorites', function () {
    $user = User::factory()->buyer()->create();
    $product = Product::factory()->approved()->create(['name' => 'Kipas Favorit']);

    $this->actingAs($user)->post("/favorit/{$product->id}")->assertRedirect();
    expect($user->favorites()->whereKey($product->id)->exists())->toBeTrue();

    $this->actingAs($user)->get('/favorit')->assertOk()->assertSee('Kipas Favorit');

    $this->actingAs($user)->post("/favorit/{$product->id}");
    expect($user->favorites()->count())->toBe(0);
});

test('hidden products cannot be favorited', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->create())->post("/favorit/{$product->id}")->assertForbidden();
});

test('sold products disappear from the favorites page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->sold()->create(['name' => 'Sudah Laku']);
    $user->favorites()->attach($product);

    $this->actingAs($user)->get('/favorit')->assertDontSee('Sudah Laku');
});

test('guests are asked to log in before using favorites', function () {
    $this->get('/favorit')->assertRedirect(route('login'));
});
