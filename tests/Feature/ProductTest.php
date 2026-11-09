<?php

use App\Enums\CodArea;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->category = Category::factory()->create();
});

/**
 * @return array<string, mixed>
 */
function productPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kalkulus Purcell Jilid 1',
        'description' => 'Dipakai satu semester, halaman lengkap.',
        'category_id' => Category::query()->value('id'),
        'condition' => 'good',
        'price' => '45.000',
        'handover_method' => 'cod',
        'cod_areas' => ['inside', 'around'],
        'location' => 'Asrama ITK',
        'photos' => [fakePhoto()],
    ], $overrides);
}

describe('create', function () {
    test('guests are redirected to login', function () {
        $this->get('/barang/jual')->assertRedirect(route('login'));
    });

    test('unverified users are sent to email verification', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/barang/jual')->assertRedirect(route('verification.notice'));
    });

    test('users without a whatsapp number are sent to the profile', function () {
        $user = User::factory()->withoutWhatsapp()->create();

        $this->actingAs($user)->get('/barang/jual')->assertRedirect(route('profile.edit'));
    });

    test('verified sellers see the sell form with camera and gallery buttons', function () {
        $user = User::factory()->seller()->create();

        $this->actingAs($user)->get('/barang/jual')
            ->assertOk()
            ->assertSee('capture="environment"', false)
            ->assertSee('Ambil Foto')
            ->assertSee('Pilih dari Galeri');
    });
});

describe('store', function () {
    test('creates a pending product with ordered photos', function () {
        $user = User::factory()->seller()->create();

        $response = $this->actingAs($user)->post('/barang', productPayload([
            'photos' => [fakePhoto('a.jpg'), fakePhoto('b.jpg')],
            'photo_order' => ['n:1', 'n:0'],
        ]));

        $response->assertRedirect(route('my-products.index', ['status' => 'pending']));

        $product = $user->products()->with('images')->sole();
        expect($product->status)->toBe(ProductStatus::Pending)
            ->and($product->price)->toBe(45000)
            ->and($product->cod_areas->all())->toBe([CodArea::Inside, CodArea::Around])
            ->and($product->images)->toHaveCount(2)
            ->and($product->images->pluck('order')->all())->toBe([0, 1]);

        $product->images->each(fn ($image) => Storage::disk('public')->assertExists($image->path));
    });

    test('donations are saved with a price of zero', function () {
        $user = User::factory()->seller()->create();

        $this->actingAs($user)->post('/barang', productPayload([
            'is_donation' => '1',
            'price' => '90000',
        ]))->assertSessionHasNoErrors();

        $product = $user->products()->sole();
        expect($product->is_donation)->toBeTrue()
            ->and($product->price)->toBe(0)
            ->and($product->formatted_price)->toBe('Gratis');
    });

    test('requires at least one photo', function () {
        $user = User::factory()->seller()->create();

        $this->actingAs($user)
            ->post('/barang', productPayload(['photos' => []]))
            ->assertSessionHasErrors('photos');

        expect(Product::query()->count())->toBe(0);
    });

    test('rejects more than five photos', function () {
        $user = User::factory()->seller()->create();

        $this->actingAs($user)
            ->post('/barang', productPayload(['photos' => array_map(fn () => fakePhoto(), range(1, 6))]))
            ->assertSessionHasErrors('photos');
    });

    test('rejects files that are not images', function () {
        $user = User::factory()->seller()->create();

        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, '<?php echo 1;');
        $disguisedScript = new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true);

        $this->actingAs($user)
            ->post('/barang', productPayload(['photos' => [$disguisedScript]]))
            ->assertSessionHasErrors('photos.0');

        expect(Product::query()->count())->toBe(0);
    });

    test('requires at least one cod area', function () {
        $user = User::factory()->seller()->create();

        $this->actingAs($user)
            ->post('/barang', productPayload(['cod_areas' => []]))
            ->assertSessionHasErrors('cod_areas');
    });
});

describe('update', function () {
    test('editing an approved product sends it back to moderation and syncs photos', function () {
        $user = User::factory()->seller()->create();
        $product = Product::factory()->for($user, 'seller')->approved()->create(['category_id' => $this->category->id]);
        $kept = $product->images()->create(['path' => 'products/kept.jpg', 'order' => 0]);
        $removed = $product->images()->create(['path' => 'products/removed.jpg', 'order' => 1]);
        Storage::disk('public')->put('products/removed.jpg', 'x');

        $this->actingAs($user)->put("/barang/{$product->id}", productPayload([
            'name' => 'Nama Baru',
            'photos' => [fakePhoto()],
            'photo_order' => ['n:0', "e:{$kept->id}"],
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        expect($product->status)->toBe(ProductStatus::Pending)
            ->and($product->name)->toBe('Nama Baru')
            ->and($product->images()->pluck('id')->last())->toBe($kept->id)
            ->and($product->images()->count())->toBe(2)
            ->and($kept->fresh()->order)->toBe(1)
            ->and($removed->fresh())->toBeNull();

        Storage::disk('public')->assertMissing('products/removed.jpg');
    });

    test('photos of another product cannot be attached', function () {
        $user = User::factory()->seller()->create();
        $product = Product::factory()->for($user, 'seller')->create(['category_id' => $this->category->id]);
        $foreignImage = Product::factory()->create(['category_id' => $this->category->id])
            ->images()->create(['path' => 'products/foreign.jpg', 'order' => 0]);

        $this->actingAs($user)->put("/barang/{$product->id}", productPayload([
            'photos' => [],
            'photo_order' => ["e:{$foreignImage->id}"],
        ]))->assertSessionHasErrors('photos');

        expect($foreignImage->fresh()->product_id)->not->toBe($product->id);
    });

    test('other users cannot edit the product', function () {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $intruder = User::factory()->seller()->create();

        $this->actingAs($intruder)->get("/barang/{$product->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/barang/{$product->id}", productPayload())->assertForbidden();
    });

    test('sold products cannot be edited', function () {
        $user = User::factory()->seller()->create();
        $product = Product::factory()->for($user, 'seller')->sold()->create(['category_id' => $this->category->id]);

        $this->actingAs($user)->get("/barang/{$product->id}/edit")->assertForbidden();
    });
});

describe('mark sold and destroy', function () {
    test('owners can mark an approved product as sold', function () {
        $user = User::factory()->seller()->create();
        $product = Product::factory()->for($user, 'seller')->approved()->create(['category_id' => $this->category->id]);

        $this->actingAs($user)->patch("/barang/{$product->id}/terjual")->assertRedirect();

        expect($product->refresh()->status)->toBe(ProductStatus::Sold)
            ->and($product->sold_at)->not->toBeNull();
    });

    test('pending products cannot be marked as sold', function () {
        $user = User::factory()->seller()->create();
        $product = Product::factory()->for($user, 'seller')->create(['category_id' => $this->category->id]);

        $this->actingAs($user)->patch("/barang/{$product->id}/terjual")->assertForbidden();
    });

    test('owners can delete a product and its photo files', function () {
        $user = User::factory()->seller()->create();
        $product = Product::factory()->for($user, 'seller')->create(['category_id' => $this->category->id]);
        $product->images()->create(['path' => 'products/a.jpg', 'order' => 0]);
        Storage::disk('public')->put('products/a.jpg', 'x');

        $this->actingAs($user)->delete("/barang/{$product->id}")->assertRedirect(route('my-products.index'));

        expect($product->fresh())->toBeNull();
        Storage::disk('public')->assertMissing('products/a.jpg');
    });

    test('other users cannot delete the product', function () {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs(User::factory()->seller()->create())
            ->delete("/barang/{$product->id}")
            ->assertForbidden();

        expect($product->fresh())->not->toBeNull();
    });
});

describe('my products', function () {
    test('only lists the signed in seller products filtered by status', function () {
        $user = User::factory()->seller()->create();
        Product::factory()->for($user, 'seller')->approved()->create(['name' => 'Barang Tayang', 'category_id' => $this->category->id]);
        Product::factory()->for($user, 'seller')->needsRevision('Foto buram')->create(['name' => 'Barang Revisi', 'category_id' => $this->category->id]);
        Product::factory()->create(['name' => 'Barang Orang Lain', 'category_id' => $this->category->id]);

        $this->actingAs($user)->get('/barang-saya?status=needs_revision')
            ->assertOk()
            ->assertSee('Barang Revisi')
            ->assertSee('Foto buram')
            ->assertDontSee('Barang Tayang')
            ->assertDontSee('Barang Orang Lain');
    });
});
