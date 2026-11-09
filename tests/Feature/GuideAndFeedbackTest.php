<?php

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('the guide is public and searchable, with the admin chapter only for admins', function () {
    $this->get('/panduan')->assertOk()->assertSee('Untuk Pembeli')->assertDontSee('Untuk Admin');
    $this->get('/panduan/aturan')->assertOk()->assertSee('Tahapan sanksi');
    $this->get('/panduan/admin')->assertNotFound();
    $this->get('/panduan/tidak-ada')->assertNotFound();

    $this->get('/panduan?q=kode serah terima')->assertOk()->assertSee('Hasil untuk')->assertSee('Untuk Penjual');

    $this->actingAs(User::factory()->admin()->create())->get('/panduan/admin')->assertOk();
});

test('anyone can send feedback with a score from 1 to 5', function () {
    $this->post('/masukan', ['score' => 4, 'comment' => 'Mudah dipakai', 'page' => 'http://localhost/katalog?q=buku'])
        ->assertSessionHasNoErrors();
    $this->actingAs($user = User::factory()->create())->post('/masukan', ['score' => 5]);

    $this->post('/masukan', ['score' => 6])->assertSessionHasErrorsIn('feedback', 'score');

    expect(Feedback::query()->count())->toBe(2)
        ->and(Feedback::query()->oldest('id')->first()->page)->toBe('/katalog')
        ->and(Feedback::query()->latest('id')->first()->user_id)->toBe($user->id);
});

test('the admin dashboard and feedback page summarise the data', function () {
    Feedback::query()->create(['score' => 4]);
    Feedback::query()->create(['score' => 2]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('3,0 / 5');
    $this->actingAs($admin)->get('/admin/masukan')->assertOk()->assertSee('dari 2 masukan');
    $this->actingAs(User::factory()->create())->get('/admin/masukan')->assertForbidden();
});

test('the guide PDF is served when generated, with the admin version only for admins', function () {
    Storage::fake('local');
    Storage::disk('local')->put('guide/buku-panduan.pdf', '%PDF-pengguna');
    Storage::disk('local')->put('guide/buku-panduan-admin.pdf', '%PDF-admin');

    $guest = $this->get('/panduan.pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($guest->streamedContent())->toBe('%PDF-pengguna');

    $admin = $this->actingAs(User::factory()->admin()->create())->get('/panduan.pdf')->assertOk();
    expect($admin->streamedContent())->toBe('%PDF-admin');
});

test('without a generated PDF the download falls back to the printable guide', function () {
    Storage::fake('local');

    $this->get('/panduan.pdf')->assertRedirect(route('guide.print'));
    $this->get('/panduan/cetak')->assertOk()
        ->assertSee('Daftar isi')->assertSee('Tahapan sanksi')
        ->assertDontSee('Untuk Admin');
    $this->actingAs(User::factory()->admin()->create())->get('/panduan/cetak')->assertSee('Untuk Admin');
});

test('guide:pdf fails clearly when Chrome cannot be found', function () {
    config(['jualin.chrome_path' => '/tidak/ada/chrome']);

    $this->artisan('guide:pdf')
        ->expectsOutputToContain('Chrome/Chromium tidak ditemukan')
        ->assertFailed();
});
