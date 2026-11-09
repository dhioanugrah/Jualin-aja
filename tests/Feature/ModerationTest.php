<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductModerated;
use Illuminate\Support\Facades\Notification;

test('non admins cannot open the moderation queue', function () {
    $this->actingAs(User::factory()->seller()->create())->get('/admin/moderasi')->assertForbidden();
});

test('guests are redirected to login from the admin panel', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('admins see pending products in the queue', function () {
    Product::factory()->create(['name' => 'Barang Menunggu']);
    Product::factory()->approved()->create(['name' => 'Barang Tayang']);

    $this->actingAs(User::factory()->admin()->create())->get('/admin/moderasi')
        ->assertOk()
        ->assertSee('Barang Menunggu')
        ->assertDontSee('Barang Tayang');
});

test('admins can approve a product and the seller is notified', function () {
    Notification::fake();
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/admin/moderasi/{$product->id}", ['decision' => 'approved'])
        ->assertRedirect();

    expect($product->refresh()->status)->toBe(ProductStatus::Approved)
        ->and($product->moderation_note)->toBeNull();
    Notification::assertSentTo($product->seller, ProductModerated::class);
});

test('requesting a revision requires a note', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/admin/moderasi/{$product->id}", ['decision' => 'needs_revision', 'note' => ''])
        ->assertSessionHasErrors('note');

    expect($product->refresh()->status)->toBe(ProductStatus::Pending);
});

test('requesting a revision stores the note for the seller', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/admin/moderasi/{$product->id}", ['decision' => 'needs_revision', 'note' => 'Foto kurang jelas.'])
        ->assertSessionHasNoErrors();

    expect($product->refresh()->status)->toBe(ProductStatus::NeedsRevision)
        ->and($product->moderation_note)->toBe('Foto kurang jelas.');
    expect($product->seller->notifications()->sole()->data['title'])->toBe('Barang kamu perlu diperbaiki');
});

test('admins cannot moderate a product that is not pending', function () {
    $product = Product::factory()->sold()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/admin/moderasi/{$product->id}", ['decision' => 'approved'])
        ->assertForbidden();

    expect($product->refresh()->status)->toBe(ProductStatus::Sold);
});

test('admins cannot mark a product as sold through moderation', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/admin/moderasi/{$product->id}", ['decision' => 'sold'])
        ->assertSessionHasErrors('decision');
});
