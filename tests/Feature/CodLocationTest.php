<?php

use App\Enums\CodArea;
use App\Models\CodLocation;
use App\Models\User;

test('admins can add and update cod locations', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/lokasi-cod', [
        'name' => 'Lobi Gedung B',
        'area' => 'inside',
        'maps_url' => 'https://maps.google.com/?q=itk',
    ])->assertSessionHasNoErrors();

    $location = CodLocation::query()->sole();
    expect($location->area)->toBe(CodArea::Inside)->and($location->is_active)->toBeTrue();

    $this->actingAs($admin)->put("/admin/lokasi-cod/{$location->id}", [
        'name' => 'Kafe Karang Joang',
        'area' => 'around',
    ])->assertSessionHasNoErrors();

    $location->refresh();
    expect($location->name)->toBe('Kafe Karang Joang')
        ->and($location->area)->toBe(CodArea::Around)
        ->and($location->is_active)->toBeFalse();

    $this->actingAs($admin)->get('/admin/lokasi-cod')->assertOk()->assertSee('Kafe Karang Joang');
});

test('non admins cannot manage cod locations', function () {
    $this->actingAs(User::factory()->create())->get('/admin/lokasi-cod')->assertForbidden();
    $this->actingAs(User::factory()->create())->post('/admin/lokasi-cod', ['name' => 'X', 'area' => 'inside'])->assertForbidden();
});
