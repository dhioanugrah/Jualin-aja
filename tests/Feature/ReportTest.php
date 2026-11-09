<?php

use App\Enums\ProductStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

test('verified users can report a product or a seller, once while the report is open', function () {
    $product = Product::factory()->approved()->create();
    $reporter = User::factory()->create();

    $this->actingAs($reporter)->post('/laporkan', ['type' => 'barang', 'id' => $product->id, 'reason' => ReportReason::FakeInfo->value])
        ->assertSessionHasNoErrors();
    $this->actingAs($reporter)->post('/laporkan', ['type' => 'barang', 'id' => $product->id, 'reason' => ReportReason::Fraud->value]);
    $this->actingAs($reporter)->post('/laporkan', ['type' => 'lapak', 'id' => $product->user_id, 'reason' => ReportReason::Fraud->value])
        ->assertSessionHasNoErrors();

    expect(Report::query()->count())->toBe(2)
        ->and($product->reports()->sole()->reason)->toBe(ReportReason::FakeInfo)
        ->and($product->seller->reports()->sole()->status)->toBe(ReportStatus::Open);
});

test('reports need a note when the reason is other, and users cannot report themselves', function () {
    $product = Product::factory()->approved()->create();

    $this->actingAs(User::factory()->create())->post('/laporkan', ['type' => 'barang', 'id' => $product->id, 'reason' => ReportReason::Other->value])
        ->assertSessionHasErrors('note');
    $this->actingAs($product->seller)->post('/laporkan', ['type' => 'barang', 'id' => $product->id, 'reason' => ReportReason::Fraud->value])
        ->assertSessionHasErrors('report');
    $this->actingAs(User::factory()->unverified()->create())->post('/laporkan', ['type' => 'barang', 'id' => $product->id, 'reason' => ReportReason::Fraud->value])
        ->assertRedirect(route('verification.notice'));

    expect(Report::query()->count())->toBe(0);
});

test('admins can hide a reported product, closing every open report on it and notifying everyone', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->approved()->create();
    [$first, $second] = User::factory()->count(2)->create();
    $report = $product->reports()->create(['reporter_id' => $first->id, 'reason' => ReportReason::Prohibited, 'status' => ReportStatus::Open]);
    $product->reports()->create(['reporter_id' => $second->id, 'reason' => ReportReason::Fraud, 'status' => ReportStatus::Open]);

    $this->actingAs($admin)->patch("/admin/laporan/{$report->id}", ['action' => 'hide_product', 'admin_note' => 'Barang terlarang.'])
        ->assertSessionHasNoErrors();

    expect($product->refresh()->status)->toBe(ProductStatus::Hidden)
        ->and($product->moderation_note)->toBe('Barang terlarang.')
        ->and($product->reports()->where('status', ReportStatus::Resolved)->count())->toBe(2);
    Notification::assertSentTo([$first, $second, $product->seller], TransactionUpdate::class);

    $this->actingAs(User::factory()->create())->get("/barang/{$product->slug}")->assertForbidden();
    $this->actingAs($product->seller)->get("/barang/{$product->id}/edit")->assertForbidden();
});

test('admins can deactivate a reported account or dismiss the report', function () {
    $admin = User::factory()->admin()->create();
    $seller = User::factory()->create();
    $reporter = User::factory()->create();
    $accountReport = $seller->reports()->create(['reporter_id' => $reporter->id, 'reason' => ReportReason::Fraud, 'status' => ReportStatus::Open]);
    $product = Product::factory()->approved()->create();
    $productReport = $product->reports()->create(['reporter_id' => $reporter->id, 'reason' => ReportReason::FakeInfo, 'status' => ReportStatus::Open]);

    $this->actingAs($admin)->patch("/admin/laporan/{$accountReport->id}", ['action' => 'deactivate_user']);
    $this->actingAs($admin)->patch("/admin/laporan/{$productReport->id}", ['action' => 'dismiss']);

    expect($seller->refresh()->is_active)->toBeFalse()
        ->and($productReport->refresh()->status)->toBe(ReportStatus::Dismissed)
        ->and($product->refresh()->status)->toBe(ProductStatus::Approved);
});

test('admin accounts cannot be reported as a seller or deactivated through a report', function () {
    $admin = User::factory()->admin()->create();
    $reporter = User::factory()->create();

    $this->actingAs($reporter)->post('/laporkan', ['type' => 'lapak', 'id' => $admin->id, 'reason' => ReportReason::Fraud->value])
        ->assertNotFound();

    $report = $admin->reports()->create(['reporter_id' => $reporter->id, 'reason' => ReportReason::Fraud, 'status' => ReportStatus::Open]);
    $this->actingAs(User::factory()->admin()->create())->patch("/admin/laporan/{$report->id}", ['action' => 'deactivate_user'])
        ->assertSessionHasErrors('report');

    expect($admin->refresh()->is_active)->toBeTrue()
        ->and($report->refresh()->status)->toBe(ReportStatus::Open);
});

test('only admins can open the report queue', function () {
    $this->actingAs(User::factory()->create())->get('/admin/laporan')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin/laporan')->assertOk();
});
