<?php

namespace App\Services;

use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Models\Booking;
use App\Models\User;
use App\Models\Violation;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

/**
 * Mencatat pelanggaran dan menerapkan sanksi bertahap (Proposal 3.3 lapis 8):
 * 1 pelanggaran = peringatan, 2 dalam 30 hari = tidak bisa booking 3 hari,
 * 3 dalam 30 hari = tidak bisa booking 14 hari dan ditinjau admin.
 */
class ViolationRecorder
{
    public function record(User $user, Booking $booking, ViolationType $type, ?string $note = null): Violation
    {
        $violation = $user->violations()->create([
            'booking_id' => $booking->id,
            'type' => $type,
            'status' => ViolationStatus::Active,
            'note' => $note,
        ]);

        $this->applySanction($user, $type);

        return $violation;
    }

    /**
     * Cabut pelanggaran (sengketa diterima atau keputusan admin), lalu hitung ulang sanksinya.
     */
    public function revoke(Violation $violation, ?string $note = null): void
    {
        $violation->forceFill([
            'status' => ViolationStatus::Revoked,
            'note' => trim(($violation->note ? $violation->note.' ' : '').($note ?? '')) ?: null,
        ])->save();

        $this->recalculate($violation->user);
    }

    /**
     * Buka blokir booking bila jumlah pelanggaran aktif sudah di bawah ambang sanksi.
     */
    public function recalculate(User $user): void
    {
        if ($this->blockDaysFor($user->recentViolationsCount()) === null && $user->isBookingBlocked()) {
            $user->forceFill(['booking_blocked_until' => null])->save();
        }
    }

    private function applySanction(User $user, ViolationType $type): void
    {
        $count = $user->recentViolationsCount();
        $blockDays = $this->blockDaysFor($count);
        $url = route('inbox');

        if ($blockDays === null) {
            $user->notify(new TransactionUpdate(
                'Peringatan pelanggaran',
                "Tercatat pelanggaran \"{$type->label()}\". Pelanggaran berikutnya dalam 30 hari membuat akunmu tidak bisa booking sementara.",
                $url,
                'warning',
            ));

            return;
        }

        $until = now()->addDays($blockDays);

        if ($user->booking_blocked_until === null || $user->booking_blocked_until->lt($until)) {
            $user->forceFill(['booking_blocked_until' => $until])->save();
        }

        $user->notify(new TransactionUpdate(
            'Akun tidak bisa booking sementara',
            "Kamu punya {$count} pelanggaran dalam 30 hari terakhir. Booking baru ditutup sampai {$until->translatedFormat('j F Y H:i')}.",
            $url,
            'danger',
        ));

        if ($count >= max(array_keys(config('jualin.sanctions')))) {
            Notification::send(
                User::query()->where('role', 'admin')->get(),
                new TransactionUpdate(
                    'Akun perlu ditinjau',
                    "{$user->name} mencapai {$count} pelanggaran dalam 30 hari terakhir.",
                    route('admin.dashboard'),
                    'warning',
                ),
            );
        }
    }

    private function blockDaysFor(int $count): ?int
    {
        $days = null;

        foreach (config('jualin.sanctions') as $threshold => $blockDays) {
            if ($count >= $threshold) {
                $days = $blockDays;
            }
        }

        return $days;
    }
}
