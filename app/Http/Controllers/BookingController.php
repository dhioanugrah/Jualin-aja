<?php

namespace App\Http\Controllers;

use App\Enums\CodArea;
use App\Enums\UserMode;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\DisputeRequest;
use App\Models\Booking;
use App\Models\CodLocation;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Booking COD (F9): ajukan, terima/tolak, batal, konfirmasi kehadiran,
 * kode serah terima, laporan tidak datang, dan bantahan.
 */
class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function create(Request $request, Product $product): View
    {
        Gate::authorize('create', [Booking::class, $product]);

        $user = $request->user();
        $offer = $request->filled('penawaran')
            ? Offer::query()->whereKey($request->integer('penawaran'))->where('buyer_id', $user->id)->whereBelongsTo($product)->first()
            : null;

        return view('bookings.create', [
            'product' => $product->load(['cover', 'seller']),
            'offer' => $offer?->isBookable() ? $offer : null,
            'locations' => CodLocation::query()
                ->where('is_active', true)
                ->whereIn('area', $product->cod_areas->map(fn (CodArea $area): string => $area->value))
                ->orderBy('name')
                ->get()
                ->groupBy(fn (CodLocation $location): string => $location->area->value),
            'activeBookings' => $user->activeBookingsCount(),
            'isBlocked' => $user->isBookingBlocked(),
        ]);
    }

    public function store(BookingRequest $request, Product $product): RedirectResponse
    {
        $offer = $request->filled('offer_id')
            ? Offer::query()->find($request->integer('offer_id'))
            : null;

        $booking = $this->bookings->create($request->user(), $product, $request->validated(), $offer);

        return redirect()->route('bookings.show', $booking)
            ->with('toast', "Booking {$booking->code} terkirim. Penjual punya waktu sampai {$booking->respond_by->translatedFormat('j M H:i')} untuk merespons.");
    }

    public function show(Request $request, Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['product.cover', 'buyer', 'seller', 'codLocation', 'offer', 'reviews.reviewer']);
        $user = $request->user();
        $isBuyer = $booking->isBuyer($user);

        return view('bookings.show', [
            'booking' => $booking,
            'isBuyer' => $isBuyer,
            'counterpart' => $isBuyer ? $booking->seller : $booking->buyer,
            'counterpartReliability' => $isBuyer
                ? $booking->seller->reliability(UserMode::Seller)
                : $booking->buyer->reliability(UserMode::Buyer),
            'counterpartRating' => $isBuyer
                ? $booking->seller->ratingAs(UserMode::Seller)
                : $booking->buyer->ratingAs(UserMode::Buyer),
            'handoverCode' => $isBuyer ? Booking::handoverCodeFor($booking->code) : null,
            'canReview' => $booking->canBeReviewedBy($user),
            'reviewTags' => config('jualin.review_tags.'.$booking->reviewDirectionFor($user)->value, []),
        ]);
    }

    public function accept(Booking $booking): RedirectResponse
    {
        Gate::authorize('respond', $booking);
        $this->bookings->accept($booking);

        return back()->with('toast', 'Booking diterima. Ingat konfirmasi kehadiran 3 jam sebelum COD.');
    }

    public function reject(Booking $booking): RedirectResponse
    {
        Gate::authorize('respond', $booking);
        $this->bookings->reject($booking);

        return back()->with('toast', 'Booking ditolak.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);
        $this->bookings->cancel($booking, $request->user());

        return back()->with('toast', 'Booking dibatalkan.');
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('confirm', $booking);
        $this->bookings->confirm($booking, $request->user());

        return back()->with('toast', 'Terima kasih! Kehadiranmu sudah dikonfirmasi.');
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('complete', $booking);

        if ($booking->isSeller($request->user())) {
            $request->validate(['code' => ['required', 'digits:4']], [], ['code' => 'kode serah terima']);
        }

        $this->bookings->complete($booking, $request->user(), $request->input('code'));

        return back()->with('toast', 'Transaksi selesai. Barang ditandai terjual.');
    }

    public function noShow(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('reportNoShow', $booking);
        $this->bookings->reportNoShow($booking, $request->user());

        return back()->with('toast', 'Laporan tercatat. Pihak lain bisa membantah dalam 24 jam.');
    }

    /**
     * Tombol WA ke pihak lain dalam booking aktif. Nomor tetap tidak ditampilkan sebagai teks.
     */
    public function whatsapp(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        $counterpart = $booking->isBuyer($request->user()) ? $booking->seller : $booking->buyer;

        if (blank($counterpart->whatsapp)) {
            return back()->withErrors(['booking' => "{$counterpart->name} belum mengisi nomor WhatsApp."]);
        }

        $message = "Halo {$counterpart->name}, soal booking {$booking->code} ({$booking->product->name}) di Jualin Aja ITK.";

        return redirect()->away('https://wa.me/'.User::normalizeWhatsapp($counterpart->whatsapp).'?text='.rawurlencode($message));
    }

    public function dispute(DisputeRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookings->dispute($booking, $request->user(), $request->validated('dispute_note'));

        return back()->with('toast', 'Bantahan terkirim dan masuk antrean admin.');
    }
}
