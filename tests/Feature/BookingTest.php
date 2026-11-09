<?php

use App\Enums\BookingStatus;
use App\Enums\CodArea;
use App\Enums\OfferStatus;
use App\Enums\ProductStatus;
use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Models\Booking;
use App\Models\CodLocation;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 10)->setTime(8, 0));
    $this->location = CodLocation::factory()->create(['area' => CodArea::Inside]);
});

/**
 * @return array<string, mixed>
 */
function bookingInput(CodLocation $location, array $overrides = []): array
{
    return [
        'cod_area' => $location->area->value,
        'cod_location_id' => $location->id,
        'cod_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i'),
        'note' => 'Saya pakai jaket biru.',
        ...$overrides,
    ];
}

test('a buyer can book a product and the seller is notified', function () {
    Notification::fake();
    $product = Product::factory()->approved()->create(['price' => 40_000]);
    $buyer = User::factory()->buyer()->create();

    $response = $this->actingAs($buyer)->post("/barang/{$product->id}/booking", bookingInput($this->location));

    $booking = Booking::query()->sole();
    $response->assertRedirect(route('bookings.show', $booking));
    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->code)->toMatch('/^JAITK-[A-Z0-9]{5}$/')
        ->and($booking->agreed_price)->toBe(40_000)
        ->and($booking->seller_id)->toBe($product->user_id)
        ->and($booking->respond_by->equalTo(now()->addHours(12)))->toBeTrue();
    Notification::assertSentTo($product->seller, TransactionUpdate::class);
});

test('booking from an accepted offer uses the offer price', function () {
    $offer = Offer::factory()->accepted()->create(['price' => 30_000]);

    $this->actingAs($offer->buyer)->post("/barang/{$offer->product_id}/booking", bookingInput($this->location, ['offer_id' => $offer->id]))
        ->assertSessionHasNoErrors();

    expect(Booking::query()->sole()->agreed_price)->toBe(30_000);
});

test('an expired offer cannot be used for booking', function () {
    $offer = Offer::factory()->accepted()->create(['expires_at' => now()->subMinute()]);

    $this->actingAs($offer->buyer)->post("/barang/{$offer->product_id}/booking", bookingInput($this->location, ['offer_id' => $offer->id]))
        ->assertSessionHasErrors('offer_id');
});

test('the cod area must be served by the seller and match the location', function () {
    $product = Product::factory()->approved()->create(['cod_areas' => [CodArea::Inside]]);
    $around = CodLocation::factory()->around()->create();
    $buyer = User::factory()->create();

    $this->actingAs($buyer)->post("/barang/{$product->id}/booking", bookingInput($around))
        ->assertSessionHasErrors('cod_area');

    $this->actingAs($buyer)->post("/barang/{$product->id}/booking", bookingInput($this->location, ['cod_location_id' => $around->id]))
        ->assertSessionHasErrors('cod_location_id');

    $inactive = CodLocation::factory()->create(['is_active' => false]);
    $this->actingAs($buyer)->post("/barang/{$product->id}/booking", bookingInput($inactive))
        ->assertSessionHasErrors('cod_location_id');
});

test('cod time must be at least one hour ahead and between 07.00 and 21.00', function (string $time) {
    $product = Product::factory()->approved()->create();

    $this->actingAs(User::factory()->create())
        ->post("/barang/{$product->id}/booking", bookingInput($this->location, ['cod_at' => $time]))
        ->assertSessionHasErrors('cod_at');
})->with([
    'kurang dari 1 jam' => '2026-11-10 08:30',
    'terlalu pagi' => '2026-11-11 06:30',
    'terlalu malam' => '2026-11-11 21:30',
]);

test('a buyer can have at most two active bookings', function () {
    $buyer = User::factory()->buyer()->create();
    Booking::factory()->for($buyer, 'buyer')->create();
    Booking::factory()->accepted()->for($buyer, 'buyer')->create();
    $product = Product::factory()->approved()->create();

    $this->actingAs($buyer)->post("/barang/{$product->id}/booking", bookingInput($this->location))
        ->assertSessionHasErrors('booking');
});

test('a buyer cannot book the same product twice while a booking is active', function () {
    $booking = Booking::factory()->create();

    $this->actingAs($booking->buyer)->post("/barang/{$booking->product_id}/booking", bookingInput($this->location))
        ->assertSessionHasErrors('booking');
});

test('a sanctioned buyer cannot book', function () {
    $buyer = User::factory()->create(['booking_blocked_until' => now()->addDays(3)]);
    $product = Product::factory()->approved()->create();

    $this->actingAs($buyer)->post("/barang/{$product->id}/booking", bookingInput($this->location))
        ->assertSessionHasErrors('booking');
});

test('sellers cannot book their own products and unavailable products cannot be booked', function () {
    $product = Product::factory()->approved()->create();
    $sold = Product::factory()->sold()->create();

    $this->actingAs($product->seller)->post("/barang/{$product->id}/booking", bookingInput($this->location))->assertForbidden();
    $this->actingAs(User::factory()->create())->post("/barang/{$sold->id}/booking", bookingInput($this->location))->assertForbidden();
});

test('only the two parties can view a booking', function () {
    $booking = Booking::factory()->create();

    $this->actingAs($booking->buyer)->get("/booking/{$booking->code}")->assertOk();
    $this->actingAs($booking->seller)->get("/booking/{$booking->code}")->assertOk();
    $this->actingAs(User::factory()->create())->get("/booking/{$booking->code}")->assertForbidden();
});

test('only the buyer sees the handover code and it is stored hashed', function () {
    $booking = Booking::factory()->accepted()->create();
    $code = Booking::handoverCodeFor($booking->code);

    expect($booking->getRawOriginal('handover_code'))->not->toBe($code);

    $this->actingAs($booking->buyer)->get("/booking/{$booking->code}")->assertSee($code);
    $this->actingAs($booking->seller)->get("/booking/{$booking->code}")->assertDontSee("'{$code}'", false);
});

test('the seller accepts a booking and the buyer is notified', function () {
    Notification::fake();
    $booking = Booking::factory()->create();

    $this->actingAs($booking->seller)->patch("/booking/{$booking->code}/terima")->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::Accepted)
        ->and($booking->accepted_at)->not->toBeNull();
    Notification::assertSentTo($booking->buyer, TransactionUpdate::class);
});

test('a product can only have one accepted booking while others queue', function () {
    $first = Booking::factory()->accepted()->create();
    $second = Booking::factory()->for($first->product)->create();

    $this->actingAs($first->seller)->patch("/booking/{$second->code}/terima")->assertSessionHasErrors('booking');

    expect($second->refresh()->status)->toBe(BookingStatus::Pending);
});

test('buyers cannot accept their own booking', function () {
    $booking = Booking::factory()->create();

    $this->actingAs($booking->buyer)->patch("/booking/{$booking->code}/terima")->assertForbidden();
});

test('attendance can only be confirmed within three hours of the cod time', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(5 * 60)->create();

    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/konfirmasi")->assertSessionHasErrors('booking');

    $this->travel(3)->hours();
    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/konfirmasi")->assertSessionHasNoErrors();

    expect($booking->refresh()->buyer_confirmed_at)->not->toBeNull()
        ->and($booking->seller_confirmed_at)->toBeNull();
});

test('the seller completes the booking with the buyer handover code', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(60)->create();
    $queued = Booking::factory()->for($booking->product)->create();
    $offer = Offer::factory()->for($booking->product)->create();

    $this->actingAs($booking->seller)
        ->post("/booking/{$booking->code}/serah-terima", ['code' => Booking::handoverCodeFor($booking->code)])
        ->assertSessionHasNoErrors();

    expect($booking->refresh()->status)->toBe(BookingStatus::Completed)
        ->and($booking->completed_by)->toBe($booking->seller_id)
        ->and($booking->product->refresh()->status)->toBe(ProductStatus::Sold)
        ->and($queued->refresh()->status)->toBe(BookingStatus::Rejected)
        ->and($offer->refresh()->status)->toBe(OfferStatus::Rejected);
});

test('a wrong handover code is rejected and attempts are limited', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(60)->create();
    $correct = Booking::handoverCodeFor($booking->code);
    $wrong = $correct === '0000' ? '1111' : '0000';

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($booking->seller)->post("/booking/{$booking->code}/serah-terima", ['code' => $wrong])->assertSessionHasErrors('code');
        $this->travel(15)->seconds();
    }

    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/serah-terima", ['code' => $correct])->assertSessionHasErrors('code');

    expect($booking->refresh()->status)->toBe(BookingStatus::Accepted);
});

test('the buyer can mark a booking complete only after the cod time', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(60)->create();

    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/serah-terima")->assertSessionHasErrors('booking');

    $this->travel(61)->minutes();
    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/serah-terima")->assertSessionHasNoErrors();

    expect($booking->refresh()->status)->toBe(BookingStatus::Completed)
        ->and($booking->completed_by)->toBe($booking->buyer_id);
});

test('cancelling an accepted booking less than three hours before cod is a violation', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(120)->create();

    $this->actingAs($booking->buyer)->patch("/booking/{$booking->code}/batal")->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->buyer->violations()->sole()->type)->toBe(ViolationType::LateCancel);
});

test('cancelling early is not a violation and frees queued bookings for the seller', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(24 * 60)->create();
    $queued = Booking::factory()->for($booking->product)->create(['respond_by' => now()->addHour()]);

    $this->actingAs($booking->buyer)->patch("/booking/{$booking->code}/batal");

    expect($booking->buyer->violations()->count())->toBe(0)
        ->and($queued->refresh()->respond_by->equalTo(now()->addHours(12)))->toBeTrue();
});

test('after the cod time either party can report a no-show, which the other can dispute within 24 hours', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(60)->create();

    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/tidak-datang")->assertSessionHasErrors('booking');

    $this->travel(2)->hours();
    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/tidak-datang")->assertSessionHasNoErrors();

    $booking->refresh();
    $violation = $booking->buyer->violations()->sole();
    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->no_show_reported_by)->toBe($booking->seller_id)
        ->and($violation->type)->toBe(ViolationType::NoShow);

    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/bantah", ['dispute_note' => 'Saya pelapornya sendiri.'])->assertForbidden();

    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/bantah", ['dispute_note' => 'Saya datang jam 10.05 dan menunggu di lobi.'])
        ->assertSessionHasNoErrors();

    expect($booking->refresh()->status)->toBe(BookingStatus::Disputed)
        ->and($violation->refresh()->status)->toBe(ViolationStatus::Disputed);
});

test('a no-show report cannot be disputed after 24 hours', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(60)->create();
    $this->travel(2)->hours();
    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/tidak-datang");

    $this->travel(25)->hours();

    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/bantah", ['dispute_note' => 'Saya datang tepat waktu kok.'])
        ->assertForbidden();
});

test('booking pages never show the counterpart whatsapp number', function () {
    $buyer = User::factory()->create(['whatsapp' => '6281277776666']);
    $booking = Booking::factory()->accepted()->for($buyer, 'buyer')->create();

    $this->actingAs($booking->seller)->get("/booking/{$booking->code}")->assertDontSee('6281277776666');
    $this->actingAs($booking->seller)->get('/pesan?tab=masuk')->assertOk()->assertSee($booking->code)->assertDontSee('6281277776666');
});

test('the booking form only offers cod points in areas the seller serves', function () {
    $product = Product::factory()->approved()->create(['cod_areas' => [CodArea::Inside]]);
    $this->location->update(['name' => 'Perpustakaan ITK']);
    CodLocation::factory()->around()->create(['name' => 'Kafe Karang Joang']);

    $this->actingAs(User::factory()->create())->get("/barang/{$product->id}/booking")
        ->assertOk()
        ->assertSee('Perpustakaan ITK')
        ->assertDontSee('Kafe Karang Joang');
});

test('the inbox shows incoming offers and bookings to the seller', function () {
    $booking = Booking::factory()->create();
    $offer = Offer::factory()->for($booking->product)->create(['price' => 12_000]);

    $this->actingAs($booking->seller)->get('/pesan?tab=masuk')
        ->assertOk()
        ->assertSee($booking->code)
        ->assertSee('Rp 12.000')
        ->assertSee(route('offers.accept', $offer));

    $this->actingAs($booking->buyer)->get('/pesan?tab=keluar')->assertOk()->assertSee($booking->code);
});
