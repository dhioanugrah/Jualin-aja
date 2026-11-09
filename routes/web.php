<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdClickController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\MyProductController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerStoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/katalog', CatalogController::class)->name('catalog');
Route::get('/lapak/{user}', SellerStoreController::class)->whereNumber('user')->name('sellers.show');
Route::get('/panduan', [GuideController::class, 'index'])->name('guide.index');
Route::get('/panduan.pdf', [GuideController::class, 'pdf'])->name('guide.pdf');
Route::get('/panduan/cetak', [GuideController::class, 'print'])->name('guide.print');
Route::get('/panduan/{chapter}', [GuideController::class, 'show'])->name('guide.show');
Route::get('/iklan/{ad}', AdClickController::class)->name('ads.click');
Route::post('/masukan', [FeedbackController::class, 'store'])->middleware('throttle:feedback')->name('feedback.store');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::get('/akun', AccountController::class)->name('account');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mulai', [ModeController::class, 'create'])->name('mode.choose');
    Route::patch('/mode', [ModeController::class, 'update'])->name('mode.update');

    Route::get('/favorit', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorit/{product}', [FavoriteController::class, 'toggle'])->whereNumber('product')->name('favorites.toggle');
    Route::get('/pesan', InboxController::class)->name('inbox');

    Route::middleware('throttle:transactions')->group(function () {
        Route::post('/barang/{product}/wa', ContactController::class)->whereNumber('product')->name('products.contact');
        Route::post('/barang/{product}/tawar', [OfferController::class, 'store'])->whereNumber('product')->name('offers.store');
        Route::post('/barang/{product}/booking', [BookingController::class, 'store'])->whereNumber('product')->name('bookings.store');
    });

    Route::patch('/penawaran/{offer}/terima', [OfferController::class, 'accept'])->name('offers.accept');
    Route::patch('/penawaran/{offer}/tolak', [OfferController::class, 'reject'])->name('offers.reject');
    Route::patch('/penawaran/{offer}/batal', [OfferController::class, 'cancel'])->name('offers.cancel');

    Route::get('/barang/{product}/booking', [BookingController::class, 'create'])->whereNumber('product')->name('bookings.create');
    Route::get('/booking/{booking:code}', [BookingController::class, 'show'])->name('bookings.show');
    Route::patch('/booking/{booking:code}/terima', [BookingController::class, 'accept'])->name('bookings.accept');
    Route::patch('/booking/{booking:code}/tolak', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::patch('/booking/{booking:code}/batal', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/booking/{booking:code}/konfirmasi', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/booking/{booking:code}/serah-terima', [BookingController::class, 'complete'])->middleware('throttle:transactions')->name('bookings.complete');
    Route::post('/booking/{booking:code}/tidak-datang', [BookingController::class, 'noShow'])->name('bookings.no-show');
    Route::post('/booking/{booking:code}/wa', [BookingController::class, 'whatsapp'])->name('bookings.whatsapp');
    Route::post('/booking/{booking:code}/bantah', [BookingController::class, 'dispute'])->name('bookings.dispute');
    Route::post('/booking/{booking:code}/ulasan', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/laporkan', [ReportController::class, 'store'])->middleware('throttle:transactions')->name('reports.store');

    Route::middleware('can-sell')->group(function () {
        Route::get('/lapak-saya', SellerDashboardController::class)->name('seller.dashboard');
        Route::get('/barang-saya', [MyProductController::class, 'index'])->name('my-products.index');

        Route::get('/barang/jual', [ProductController::class, 'create'])->name('products.create');
        Route::post('/barang', [ProductController::class, 'store'])->name('products.store');
        Route::get('/barang/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('/barang/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::delete('/barang/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
        Route::patch('/barang/{product}/terjual', [ProductController::class, 'markSold'])->whereNumber('product')->name('products.mark-sold');
        Route::post('/barang/{product}/promosi', [PromotionController::class, 'store'])->whereNumber('product')->name('products.promote');
    });
});

Route::get('/barang/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/moderasi', [Admin\ModerationController::class, 'index'])->name('moderation.index');
    Route::patch('/moderasi/{product}', [Admin\ModerationController::class, 'update'])->name('moderation.update');
    Route::get('/lokasi-cod', [Admin\CodLocationController::class, 'index'])->name('cod-locations.index');
    Route::post('/lokasi-cod', [Admin\CodLocationController::class, 'store'])->name('cod-locations.store');
    Route::put('/lokasi-cod/{codLocation}', [Admin\CodLocationController::class, 'update'])->name('cod-locations.update');

    Route::get('/iklan', [Admin\AdController::class, 'index'])->name('ads.index');
    Route::post('/iklan', [Admin\AdController::class, 'store'])->name('ads.store');
    Route::get('/iklan/{ad}/edit', [Admin\AdController::class, 'edit'])->name('ads.edit');
    Route::put('/iklan/{ad}', [Admin\AdController::class, 'update'])->name('ads.update');

    Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::patch('/laporan/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');

    Route::get('/sengketa', [Admin\DisputeController::class, 'index'])->name('disputes.index');
    Route::patch('/sengketa/{booking:code}', [Admin\DisputeController::class, 'update'])->name('disputes.update');
    Route::patch('/pelanggaran/{violation}/cabut', [Admin\DisputeController::class, 'revokeViolation'])->name('violations.revoke');
    Route::patch('/pengguna/{user}/status', [Admin\DisputeController::class, 'toggleUser'])->name('users.toggle');

    Route::get('/masukan', [Admin\FeedbackController::class, 'index'])->name('feedback.index');
});

require __DIR__.'/auth.php';
