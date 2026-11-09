<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Violation;
use App\Notifications\TransactionUpdate;
use App\Services\ViolationRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sengketa "tidak datang" dan akun yang perlu ditinjau (F13, Proposal 3.3 lapis 8).
 */
class DisputeController extends Controller
{
    public function __construct(private ViolationRecorder $violations) {}

    public function index(): View
    {
        $threshold = max(array_keys(config('jualin.sanctions')));
        $windowStart = now()->subDays((int) config('jualin.violation_window_days'));

        return view('admin.disputes.index', [
            'disputes' => Booking::query()
                ->where('status', BookingStatus::Disputed)
                ->with(['product', 'buyer', 'seller', 'codLocation', 'violations'])
                ->oldest('updated_at')
                ->get(),
            'resolved' => Booking::query()
                ->whereNotNull('dispute_resolved_at')
                ->with(['product', 'buyer', 'seller'])
                ->latest('dispute_resolved_at')
                ->take(10)
                ->get(),
            'flaggedUsers' => User::query()
                ->where('role', UserRole::Mahasiswa)
                ->withCount(['violations as recent_violations_count' => fn (Builder $query) => $query
                    ->where('status', ViolationStatus::Active)
                    ->where('created_at', '>=', $windowStart)])
                ->with(['violations' => fn ($query) => $query->where('status', '!=', ViolationStatus::Revoked)->latest()->with('booking.product')])
                ->where(fn (Builder $query) => $query
                    ->where('is_active', false)
                    ->orWhereHas('violations', fn (Builder $violation) => $violation
                        ->where('status', ViolationStatus::Active)
                        ->where('created_at', '>=', $windowStart), '>=', $threshold))
                ->orderBy('is_active')
                ->orderByDesc('recent_violations_count')
                ->get(),
        ]);
    }

    /**
     * Bantahan diterima: pelanggaran pihak yang membantah dicabut.
     * Bantahan ditolak: pelanggaran tetap berlaku.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['accepted', 'rejected'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($booking->status === BookingStatus::Disputed, 422, 'Sengketa ini sudah diputuskan.');

        $accepted = $data['outcome'] === 'accepted';

        DB::transaction(function () use ($booking, $accepted, $data, $request): void {
            $disputed = $booking->violations()
                ->where('type', ViolationType::NoShow)
                ->where('status', ViolationStatus::Disputed)
                ->with('user')
                ->get();

            foreach ($disputed as $violation) {
                if ($accepted) {
                    $this->violations->revoke($violation, 'Dicabut admin: bantahan diterima.');
                } else {
                    $violation->forceFill(['status' => ViolationStatus::Active])->save();
                }
            }

            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'dispute_outcome' => $data['outcome'],
                'dispute_resolved_by' => $request->user()->id,
                'dispute_resolved_at' => now(),
            ])->save();
        });

        $booking->loadMissing(['buyer', 'seller']);
        $message = $accepted
            ? "Admin menerima bantahan untuk booking {$booking->code}. Pelanggaran \"tidak datang\" dicabut."
            : "Admin menolak bantahan untuk booking {$booking->code}. Pelanggaran \"tidak datang\" tetap berlaku.";

        foreach ([$booking->buyer, $booking->seller] as $party) {
            $party->notify(new TransactionUpdate(
                'Sengketa diputuskan',
                $message.(filled($data['note'] ?? null) ? " Catatan admin: {$data['note']}" : ''),
                route('bookings.show', $booking),
                $accepted ? 'success' : 'warning',
            ));
        }

        return back()->with('toast', $accepted ? 'Bantahan diterima, pelanggaran dicabut.' : 'Bantahan ditolak, pelanggaran tetap berlaku.');
    }

    public function revokeViolation(Violation $violation): RedirectResponse
    {
        abort_if($violation->status === ViolationStatus::Revoked, 422);

        $this->violations->revoke($violation, 'Dicabut admin.');

        $violation->user->notify(new TransactionUpdate(
            'Pelanggaran dicabut',
            "Admin mencabut pelanggaran \"{$violation->type->label()}\" di akunmu.",
            route('inbox'),
            'success',
        ));

        return back()->with('toast', 'Pelanggaran dicabut.');
    }

    public function toggleUser(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('toast', $user->is_active ? "Akun {$user->name} diaktifkan lagi." : "Akun {$user->name} dinonaktifkan.");
    }
}
