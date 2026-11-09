<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Requests\ReportRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Tombol Laporkan di detail barang dan lapak (F13). Laporan masuk antrean admin.
 */
class ReportController extends Controller
{
    public function store(ReportRequest $request): RedirectResponse
    {
        $user = $request->user();

        /** @var Model $target */
        $target = $request->validated('type') === 'barang'
            ? Product::query()->visible()->findOrFail($request->integer('id'))
            : User::query()->where('is_active', true)->where('role', UserRole::Mahasiswa)->findOrFail($request->integer('id'));

        $ownerId = $target instanceof Product ? $target->user_id : $target->getKey();

        if ($ownerId === $user->id) {
            return back()->withErrors(['report' => 'Kamu tidak bisa melaporkan barang atau lapakmu sendiri.']);
        }

        $alreadyOpen = $target->reports()
            ->where('reporter_id', $user->id)
            ->where('status', ReportStatus::Open)
            ->exists();

        if ($alreadyOpen) {
            return back()->with('toast', 'Laporanmu sebelumnya masih ditinjau admin.');
        }

        $target->reports()->create([
            'reporter_id' => $user->id,
            'reason' => $request->validated('reason'),
            'note' => $request->validated('note'),
            'status' => ReportStatus::Open,
        ]);

        return back()->with('toast', 'Laporan terkirim. Admin akan meninjaunya, terima kasih sudah menjaga Jualin Aja ITK.');
    }
}
