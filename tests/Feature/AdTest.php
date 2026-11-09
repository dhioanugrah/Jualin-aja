<?php

use App\Enums\AdPlacement;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function liveAd(array $attributes = []): Ad
{
    return Ad::query()->create($attributes + [
        'title' => 'Open Recruitment HMIF',
        'advertiser' => 'HMIF',
        'image' => 'ads/banner.jpg',
        'url' => 'https://example.com/oprec',
        'placement' => AdPlacement::HomeTop,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
        'is_active' => true,
    ]);
}

test('admins can create and update ads with an uploaded image', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/iklan', [
        'title' => 'Bazar Buku',
        'advertiser' => 'UKM Literasi',
        'url' => 'https://example.com',
        'placement' => AdPlacement::CatalogInline->value,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addWeek()->toDateString(),
        'image' => fakePhoto('banner.jpg'),
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $ad = Ad::query()->sole();
    Storage::disk('public')->assertExists($ad->image);

    $this->actingAs($admin)->put("/admin/iklan/{$ad->id}", [
        'title' => 'Bazar Buku Murah',
        'advertiser' => 'UKM Literasi',
        'placement' => AdPlacement::HomeTop->value,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addWeek()->toDateString(),
        'is_active' => '0',
    ])->assertSessionHasNoErrors();

    expect($ad->refresh()->title)->toBe('Bazar Buku Murah')->and($ad->is_active)->toBeFalse();
});

test('non-admins cannot manage ads', function () {
    $this->actingAs(User::factory()->create())->get('/admin/iklan')->assertForbidden();
    $this->actingAs(User::factory()->create())->post('/admin/iklan', [])->assertForbidden();
});

test('live ads are shown with an ad label and count impressions; inactive or ended ads are hidden', function () {
    $live = liveAd();
    $inactive = liveAd(['title' => 'Iklan Nonaktif', 'is_active' => false]);
    $ended = liveAd(['title' => 'Iklan Kedaluwarsa', 'ends_at' => now()->subMinute()]);

    $this->get('/')->assertSee('Open Recruitment HMIF')->assertSee('Iklan')
        ->assertDontSee('Iklan Nonaktif')->assertDontSee('Iklan Kedaluwarsa');

    expect($live->refresh()->impressions)->toBe(1)
        ->and($inactive->refresh()->impressions)->toBe(0)
        ->and($ended->refresh()->impressions)->toBe(0);
});

test('ad clicks are counted and redirected; ended ads do not redirect', function () {
    $live = liveAd();
    $ended = liveAd(['ends_at' => now()->subMinute()]);

    $this->get("/iklan/{$live->id}")->assertRedirect('https://example.com/oprec');
    $this->get("/iklan/{$ended->id}")->assertNotFound();

    expect($live->refresh()->clicks)->toBe(1)->and($ended->refresh()->clicks)->toBe(0);
});
