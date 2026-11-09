<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profil');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profil', [
            'name' => 'Test User',
            'whatsapp' => '+62 812 1111 2222',
            'prodi' => 'Informatika',
            'angkatan' => 2023,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profil');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('6281211112222', $user->whatsapp);
    $this->assertSame('Informatika', $user->prodi);
    $this->assertSame(2023, $user->angkatan);
});

test('campus email and nim cannot be changed from the profile form', function () {
    $user = User::factory()->create();
    $originalEmail = $user->email;
    $originalNim = $user->nim;

    $response = $this
        ->actingAs($user)
        ->patch('/profil', [
            'name' => 'Test User',
            'whatsapp' => '081211112222',
            'email' => 'lain@student.itk.ac.id',
            'nim' => '99999999',
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();
    $this->assertSame($originalEmail, $user->email);
    $this->assertSame($originalNim, $user->nim);
    $this->assertNotNull($user->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profil', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profil')
        ->delete('/profil', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profil');

    $this->assertNotNull($user->fresh());
});
