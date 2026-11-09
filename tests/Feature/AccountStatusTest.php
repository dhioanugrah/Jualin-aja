<?php

use App\Models\User;

test('deactivated users cannot log in', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('deactivated users are logged out on their next request', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)->get('/akun')->assertRedirect(route('login'));

    $this->assertGuest();
});
