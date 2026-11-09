<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('home page only lists approved products from active sellers', function () {
    $category = Category::factory()->create();
    Product::factory()->approved()->create(['name' => 'Barang Tayang', 'category_id' => $category->id]);
    Product::factory()->create(['name' => 'Barang Menunggu', 'category_id' => $category->id]);
    Product::factory()->sold()->create(['name' => 'Barang Terjual', 'category_id' => $category->id]);
    Product::factory()->needsRevision()->create(['name' => 'Barang Revisi', 'category_id' => $category->id]);
    Product::factory()->approved()->for(User::factory()->inactive(), 'seller')->create(['name' => 'Barang Akun Nonaktif', 'category_id' => $category->id]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Barang Tayang')
        ->assertDontSee('Barang Menunggu')
        ->assertDontSee('Barang Terjual')
        ->assertDontSee('Barang Revisi')
        ->assertDontSee('Barang Akun Nonaktif');
});

test('searching from the home page goes to the catalog', function () {
    $this->get('/?q=Kalku&kategori=buku')->assertRedirect(route('catalog', ['q' => 'Kalku', 'kategori' => 'buku']));
});

test('catalog filters by search term and category', function () {
    $books = Category::factory()->create(['slug' => 'buku']);
    $other = Category::factory()->create();
    Product::factory()->approved()->create(['name' => 'Kalkulus Purcell', 'category_id' => $books->id]);
    Product::factory()->approved()->create(['name' => 'Kalkulator Casio', 'category_id' => $other->id]);

    $this->get('/katalog?q=Kalku&kategori=buku')
        ->assertSee('Kalkulus Purcell')
        ->assertDontSee('Kalkulator Casio');
});

test('approved products are visible to guests and count a view', function () {
    $product = Product::factory()->approved()->create();

    $this->get("/barang/{$product->slug}")->assertOk()->assertSee($product->name);

    expect($product->refresh()->views)->toBe(1);
});

test('pending products are hidden from other users', function () {
    $product = Product::factory()->create();

    $this->get("/barang/{$product->slug}")->assertForbidden();
    $this->actingAs(User::factory()->create())->get("/barang/{$product->slug}")->assertForbidden();
});

test('owners can preview their pending product without counting a view', function () {
    $product = Product::factory()->create();

    $this->actingAs($product->seller)->get("/barang/{$product->slug}")
        ->assertOk()
        ->assertSee('Pratinjau');

    expect($product->refresh()->views)->toBe(0);
});

test('the seller whatsapp number is never rendered on the product page', function () {
    $seller = User::factory()->create(['whatsapp' => '6281299998888']);
    $product = Product::factory()->approved()->for($seller, 'seller')->create();

    $this->actingAs(User::factory()->create())
        ->get("/barang/{$product->slug}")
        ->assertDontSee('6281299998888');
});

test('verified buyers get the contact options while guests are asked to log in', function () {
    $product = Product::factory()->approved()->create();

    $this->get("/barang/{$product->slug}")->assertSee('Masuk untuk menghubungi penjual');

    $this->actingAs(User::factory()->buyer()->create())->get("/barang/{$product->slug}")
        ->assertSee('Diskusi via WhatsApp')
        ->assertSee('Ajukan Penawaran')
        ->assertSee(route('bookings.create', $product));
});
