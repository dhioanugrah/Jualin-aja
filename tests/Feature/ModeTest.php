<?php

use App\Enums\UserMode;
use App\Models\User;

test('dashboard sends users without a mode to the mode chooser', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('mode.choose'));
});

test('dashboard sends sellers to the lapak and buyers to the home page', function (string $state, string $route) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route($route));
})->with([
    'seller' => ['seller', 'seller.dashboard'],
    'buyer' => ['buyer', 'home'],
]);

test('dashboard sends admins to the admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
});

test('users can switch to seller mode', function () {
    $user = User::factory()->buyer()->create();

    $this->actingAs($user)
        ->patch('/mode', ['mode' => 'seller'])
        ->assertRedirect(route('seller.dashboard'));

    expect($user->refresh()->active_mode)->toBe(UserMode::Seller);
});

test('users without a whatsapp number are sent to the profile before seller mode', function () {
    $user = User::factory()->buyer()->withoutWhatsapp()->create();

    $this->actingAs($user)
        ->patch('/mode', ['mode' => 'seller'])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->active_mode)->toBe(UserMode::Buyer);
});

test('unverified users cannot choose a mode', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->patch('/mode', ['mode' => 'seller'])
        ->assertRedirect(route('verification.notice'));

    expect($user->refresh()->active_mode)->toBeNull();
});

test('mode switching rejects unknown modes', function () {
    $user = User::factory()->buyer()->create();

    $this->actingAs($user)
        ->patch('/mode', ['mode' => 'admin'])
        ->assertSessionHasErrors('mode');
});
