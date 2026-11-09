<?php

namespace App\Services;

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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Alur booking COD dan aturan anti pembeli fiktif (F9, Proposal 3.3).
 * Semua aturan bisnis ada di sini supaya controller dan scheduler memakai logika yang sama.
 */
class BookingService
{
    public function __construct(private ViolationRecorder $violations) {}

    /**
     * @param  array{cod_area: string, cod_location_id: int, cod_at: string, note?: string|null}  $data
     */
    public function create(User $buyer, Product $product, array $data, ?Offer $offer = null): Booking
    {
        $this->ensureBuyerMayBook($buyer, $product);

        $area = CodArea::from($data['cod_area']);
        $codAt = Carbon::parse($data['cod_at']);

        if (! $product->acceptsCodArea($area)) {
            $this->fail('cod_area', 'Penjual tidak melayani COD di area ini.');
        }

        $location = CodLocation::query()->whereKey($data['cod_location_id'])->where('is_active', true)->first();

        if ($location === null || $location->area !== $area) {
            $this->fail('cod_location_id', 'Pilih titik COD yang tersedia untuk area ini.');
        }

        $this->ensureValidSchedule($codAt);

        if ($offer !== null && ($offer->buyer_id !== $buyer->id || $offer->product_id !== $product->id || ! $offer->isBookable())) {
            $this->fail('offer_id', 'Penawaran ini sudah tidak bisa dipakai untuk booking.');
        }

        $booking = DB::transaction(fn (): Booking => Booking::query()->create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $product->user_id,
            'offer_id' => $offer?->id,
            'agreed_price' => $offer?->price ?? $product->price,
            'cod_at' => $codAt,
            'cod_area' => $area,
            'cod_location_id' => $location->id,
            'note' => $data['note'] ?? null,
            'status' => BookingStatus::Pending,
            'respond_by' => min(now()->addHours((int) config('jualin.booking_respond_hours')), $codAt),
        ]));

        $product->seller->notify(new TransactionUpdate(
            'Booking baru masuk',
            "{$buyer->name} mengajukan COD untuk \"{$product->name}\" pada {$codAt->translatedFormat('l, j M H:i')}. Respons sebelum {$booking->respond_by->translatedFormat('j M H:i')}.",
            route('bookings.show', $booking),
        ));

        return $booking;
    }

    /**
     * Satu barang hanya punya satu booking Diterima; booking lain tetap antre sebagai cadangan.
     */
    public function accept(Booking $booking): void
    {
        $this->ensureStatus($booking, BookingStatus::Pending);

        if ($booking->respond_by !== null && $booking->respond_by->isPast()) {
            $this->fail('booking', 'Batas waktu respons booking ini sudah lewat.');
        }

        if ($booking->product->status !== ProductStatus::Approved) {
            $this->fail('booking', 'Barang ini sudah tidak tersedia.');
        }

        if ($booking->product->bookings()->where('status', BookingStatus::Accepted)->exists()) {
            $this->fail('booking', 'Barang ini sudah punya booking yang diterima. Booking lain tetap antre sebagai cadangan.');
        }

        $booking->forceFill(['status' => BookingStatus::Accepted, 'accepted_at' => now()])->save();

        $booking->buyer->notify(new TransactionUpdate(
            'Booking diterima',
            "Penjual menerima booking {$booking->code}. Konfirmasi kehadiran mulai {$booking->confirmOpensAt()->translatedFormat('j M H:i')}.",
            route('bookings.show', $booking),
            'success',
        ));
    }

    public function reject(Booking $booking): void
    {
        $this->ensureStatus($booking, BookingStatus::Pending);

        $booking->forceFill(['status' => BookingStatus::Rejected])->save();

        $booking->buyer->notify(new TransactionUpdate(
            'Booking ditolak',
            "Penjual menolak booking {$booking->code} untuk \"{$booking->product->name}\".",
            route('bookings.show', $booking),
            'warning',
        ));
    }

    /**
     * Membatalkan booking Diterima kurang dari 3 jam sebelum jadwal dicatat sebagai pelanggaran.
     */
    public function cancel(Booking $booking, User $actor): void
    {
        if (! $booking->isActive()) {
            $this->fail('booking', 'Booking ini sudah tidak aktif.');
        }

        $wasAccepted = $booking->status === BookingStatus::Accepted;
        $isLate = $wasAccepted && now()->gte($booking->cod_at->copy()->subHours((int) config('jualin.late_cancel_hours')));

        $booking->forceFill(['status' => BookingStatus::Cancelled, 'cancelled_by' => $actor->id])->save();

        if ($isLate) {
            $this->violations->record($actor, $booking, ViolationType::LateCancel);
        }

        User::query()->find($booking->counterpartIdOf($actor))?->notify(new TransactionUpdate(
            'Booking dibatalkan',
            "{$actor->name} membatalkan booking {$booking->code} untuk \"{$booking->product->name}\".",
            route('bookings.show', $booking),
            'warning',
        ));

        if ($wasAccepted) {
            $this->releaseQueue($booking->product);
        }
    }

    public function confirm(Booking $booking, User $actor): void
    {
        if (! $booking->isInConfirmWindow()) {
            $this->fail('booking', 'Konfirmasi kehadiran dibuka 3 jam sebelum jadwal COD.');
        }

        $column = $booking->isBuyer($actor) ? 'buyer_confirmed_at' : 'seller_confirmed_at';
        $booking->forceFill([$column => $booking->{$column} ?? now()])->save();

        User::query()->find($booking->counterpartIdOf($actor))?->notify(new TransactionUpdate(
            "{$actor->name} tetap datang",
            "Konfirmasi kehadiran untuk booking {$booking->code} ({$booking->cod_at->translatedFormat('H:i')}, {$booking->codLocation->name}).",
            route('bookings.show', $booking),
            'success',
        ));
    }

    /**
     * Penjual memasukkan kode 4 digit milik pembeli. Bila penjual lupa,
     * pembeli bisa menandai Selesai setelah jadwal lewat tanpa kode.
     */
    public function complete(Booking $booking, User $actor, ?string $code = null): void
    {
        $this->ensureStatus($booking, BookingStatus::Accepted);

        if ($booking->isSeller($actor)) {
            $this->verifyHandoverCode($booking, (string) $code);
        } elseif (! $booking->isPastSchedule()) {
            $this->fail('booking', 'Pembeli bisa menandai selesai setelah jadwal COD lewat.');
        }

        DB::transaction(function () use ($booking, $actor): void {
            $booking->forceFill([
                'status' => BookingStatus::Completed,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ])->save();

            $product = $booking->product;
            $product->forceFill(['status' => ProductStatus::Sold, 'sold_at' => now()])->save();

            $this->closeOtherBookingsAndOffers($booking);
        });

        User::query()->find($booking->counterpartIdOf($actor))?->notify(new TransactionUpdate(
            'Transaksi selesai',
            "Booking {$booking->code} untuk \"{$booking->product->name}\" selesai. Terima kasih sudah COD dengan aman!",
            route('bookings.show', $booking),
            'success',
        ));
    }

    /**
     * Setelah jadwal lewat, salah satu pihak bisa melaporkan pihak lain tidak datang.
     * Pihak yang dilaporkan bisa membantah dalam 24 jam.
     */
    public function reportNoShow(Booking $booking, User $reporter): void
    {
        $this->ensureStatus($booking, BookingStatus::Accepted);

        if (! $booking->isPastSchedule()) {
            $this->fail('booking', 'Laporan tidak datang baru bisa dibuat setelah jadwal COD lewat.');
        }

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'no_show_reported_by' => $reporter->id,
            'no_show_reported_at' => now(),
        ])->save();

        $absent = User::query()->findOrFail($booking->counterpartIdOf($reporter));
        $this->violations->record($absent, $booking, ViolationType::NoShow, "Dilaporkan oleh {$reporter->name}.");

        $absent->notify(new TransactionUpdate(
            'Kamu dilaporkan tidak datang',
            "{$reporter->name} melaporkan kamu tidak datang untuk booking {$booking->code}. Kalau tidak benar, bantah dalam 24 jam.",
            route('bookings.show', $booking),
            'danger',
        ));

        $this->releaseQueue($booking->product);
    }

    public function dispute(Booking $booking, User $actor, string $note): void
    {
        if (! $booking->canBeDisputedBy($actor)) {
            $this->fail('booking', 'Laporan ini sudah tidak bisa dibantah.');
        }

        DB::transaction(function () use ($booking, $actor, $note): void {
            $booking->forceFill(['status' => BookingStatus::Disputed, 'dispute_note' => $note])->save();

            $booking->violations()
                ->where('user_id', $actor->id)
                ->where('type', ViolationType::NoShow)
                ->update(['status' => ViolationStatus::Disputed]);
        });

        User::query()->find($booking->no_show_reported_by)?->notify(new TransactionUpdate(
            'Laporan tidak datang dibantah',
            "{$actor->name} membantah laporan untuk booking {$booking->code}. Admin akan meninjau sengketa ini.",
            route('bookings.show', $booking),
            'warning',
        ));
    }

    /**
     * Dijalankan scheduler. Mengembalikan jumlah booking dan penawaran yang ditutup.
     *
     * @return array{bookings: int, unconfirmed: int, offers: int}
     */
    public function expireOverdue(): array
    {
        $expired = 0;

        Booking::query()
            ->where('status', BookingStatus::Pending)
            ->where('respond_by', '<=', now())
            ->whereDoesntHave('product.bookings', fn ($query) => $query->where('status', BookingStatus::Accepted))
            ->with(['buyer', 'product'])
            ->each(function (Booking $booking) use (&$expired): void {
                $booking->forceFill(['status' => BookingStatus::Expired])->save();
                $booking->buyer->notify(new TransactionUpdate(
                    'Booking kedaluwarsa',
                    "Penjual tidak merespons booking {$booking->code} tepat waktu, jadi booking dibatalkan otomatis.",
                    route('bookings.show', $booking),
                    'warning',
                ));
                $expired++;
            });

        $unconfirmed = 0;
        $deadlineHours = (int) config('jualin.confirm_deadline_hours');

        Booking::query()
            ->where('status', BookingStatus::Accepted)
            ->where('cod_at', '<=', now()->addHours($deadlineHours))
            ->where(fn ($query) => $query->whereNull('buyer_confirmed_at')->orWhereNull('seller_confirmed_at'))
            ->with(['buyer', 'seller', 'product'])
            ->each(function (Booking $booking) use (&$unconfirmed): void {
                $this->cancelUnconfirmed($booking);
                $unconfirmed++;
            });

        $offers = Offer::query()
            ->where('status', OfferStatus::Accepted)
            ->where('expires_at', '<=', now())
            ->whereDoesntHave('booking')
            ->update(['status' => OfferStatus::Expired]);

        return ['bookings' => $expired, 'unconfirmed' => $unconfirmed, 'offers' => $offers];
    }

    /**
     * Pengingat konfirmasi kehadiran saat jendela H-3 jam dibuka.
     */
    public function sendReminders(): int
    {
        $sent = 0;

        Booking::query()
            ->where('status', BookingStatus::Accepted)
            ->whereNull('reminded_at')
            ->where('cod_at', '<=', now()->addHours((int) config('jualin.confirm_opens_hours')))
            ->where('cod_at', '>', now())
            ->with(['buyer', 'seller', 'codLocation', 'product'])
            ->each(function (Booking $booking) use (&$sent): void {
                $deadline = $booking->confirmDeadline()->translatedFormat('H:i');

                foreach ([$booking->buyer, $booking->seller] as $party) {
                    $party->notify(new TransactionUpdate(
                        'Konfirmasi kehadiran COD',
                        "COD \"{$booking->product->name}\" pukul {$booking->cod_at->translatedFormat('H:i')} di {$booking->codLocation->name}. Tekan \"Saya tetap datang\" sebelum {$deadline}, atau booking batal otomatis.",
                        route('bookings.show', $booking),
                        'warning',
                    ));
                }

                $booking->forceFill(['reminded_at' => now()])->save();
                $sent++;
            });

        return $sent;
    }

    private function cancelUnconfirmed(Booking $booking): void
    {
        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        $missing = collect([
            $booking->buyer_confirmed_at === null ? $booking->buyer : null,
            $booking->seller_confirmed_at === null ? $booking->seller : null,
        ])->filter();

        foreach ($missing as $user) {
            $this->violations->record($user, $booking, ViolationType::NoConfirm);
        }

        foreach ([$booking->buyer, $booking->seller] as $party) {
            $party->notify(new TransactionUpdate(
                'Booking batal otomatis',
                "Booking {$booking->code} dibatalkan karena konfirmasi kehadiran tidak lengkap sampai 1 jam sebelum jadwal.",
                route('bookings.show', $booking),
                'danger',
            ));
        }

        $this->releaseQueue($booking->product);
    }

    /**
     * Saat booking Diterima gagal, booking cadangan yang antre mendapat 12 jam baru untuk direspons.
     */
    private function releaseQueue(Product $product): void
    {
        $queued = $product->bookings()
            ->where('status', BookingStatus::Pending)
            ->where('cod_at', '>', now())
            ->get();

        if ($queued->isEmpty() || $product->status !== ProductStatus::Approved) {
            return;
        }

        foreach ($queued as $booking) {
            $booking->forceFill([
                'respond_by' => min(now()->addHours((int) config('jualin.booking_respond_hours')), $booking->cod_at),
            ])->save();
        }

        $product->seller->notify(new TransactionUpdate(
            'Ada booking cadangan',
            "{$queued->count()} booking cadangan untuk \"{$product->name}\" menunggu responsmu.",
            route('inbox', ['tab' => 'masuk']),
        ));
    }

    private function closeOtherBookingsAndOffers(Booking $completed): void
    {
        $others = $completed->product->bookings()->active()->whereKeyNot($completed->id)->with('buyer')->get();

        foreach ($others as $other) {
            $other->forceFill(['status' => BookingStatus::Rejected])->save();
            $other->buyer->notify(new TransactionUpdate(
                'Barang sudah terjual',
                "\"{$completed->product->name}\" sudah terjual ke pembeli lain, jadi booking {$other->code} ditutup.",
                route('bookings.show', $other),
                'warning',
            ));
        }

        $completed->product->offers()
            ->whereIn('status', [OfferStatus::Pending, OfferStatus::Accepted])
            ->when($completed->offer_id, fn ($query, int $offerId) => $query->whereKeyNot($offerId))
            ->update(['status' => OfferStatus::Rejected]);
    }

    private function verifyHandoverCode(Booking $booking, string $code): void
    {
        if (! $booking->canEnterHandoverCode()) {
            $this->fail('code', 'Kode serah terima bisa dimasukkan mulai 3 jam sebelum jadwal COD.');
        }

        $key = "handover:{$booking->id}";

        if (RateLimiter::tooManyAttempts($key, (int) config('jualin.handover_max_attempts'))) {
            $this->fail('code', 'Terlalu banyak percobaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.');
        }

        if (! Hash::check($code, $booking->handover_code)) {
            RateLimiter::hit($key, 15 * 60);
            $this->fail('code', 'Kode serah terima salah. Minta pembeli menyebutkan 4 digit kode di halaman booking-nya.');
        }

        RateLimiter::clear($key);
    }

    private function ensureBuyerMayBook(User $buyer, Product $product): void
    {
        if ($buyer->id === $product->user_id) {
            $this->fail('booking', 'Kamu tidak bisa booking barangmu sendiri.');
        }

        if (! $product->isAvailable()) {
            $this->fail('booking', 'Barang ini sudah tidak tersedia.');
        }

        if ($buyer->isBookingBlocked()) {
            $this->fail('booking', 'Akunmu tidak bisa booking sampai '.$buyer->booking_blocked_until->translatedFormat('j F Y H:i').' karena pelanggaran.');
        }

        if ($product->bookings()->active()->where('buyer_id', $buyer->id)->exists()) {
            $this->fail('booking', 'Kamu sudah punya booking aktif untuk barang ini.');
        }

        $max = (int) config('jualin.max_active_bookings');

        if ($buyer->activeBookingsCount() >= $max) {
            $this->fail('booking', "Kamu sudah punya {$max} booking aktif. Selesaikan atau batalkan salah satunya dulu.");
        }
    }

    private function ensureValidSchedule(Carbon $codAt): void
    {
        if ($codAt->lt(now()->addHours((int) config('jualin.cod_min_lead_hours')))) {
            $this->fail('cod_at', 'Waktu COD paling cepat 1 jam dari sekarang.');
        }

        $open = (int) config('jualin.cod_open_hour');
        $close = (int) config('jualin.cod_close_hour');
        $minutes = $codAt->hour * 60 + $codAt->minute;

        if ($minutes < $open * 60 || $minutes > $close * 60) {
            $this->fail('cod_at', sprintf('Jam COD hanya %02d.00–%02d.00 WITA.', $open, $close));
        }

        if ($codAt->gt(now()->addDays(14))) {
            $this->fail('cod_at', 'Jadwal COD paling lama 14 hari dari sekarang.');
        }
    }

    private function ensureStatus(Booking $booking, BookingStatus $status): void
    {
        if ($booking->status !== $status) {
            $this->fail('booking', "Booking ini berstatus {$booking->status->label()}, aksi tidak bisa dilakukan.");
        }
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
