<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register with a campus email', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'nim' => '11221099',
        'email' => '11221099@student.itk.ac.id',
        'whatsapp' => '0812-3456-7890',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('nim', '11221099')->sole();
    expect($user->whatsapp)->toBe('6281234567890')
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

test('registration rejects emails outside the campus domain', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'nim' => '11221099',
        'email' => 'test@gmail.com',
        'whatsapp' => '081234567890',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('registration rejects a nim that is already registered', function () {
    User::factory()->create(['nim' => '11221099']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'nim' => '11221099',
        'email' => 'lain@student.itk.ac.id',
        'whatsapp' => '081234567890',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('nim');
});
