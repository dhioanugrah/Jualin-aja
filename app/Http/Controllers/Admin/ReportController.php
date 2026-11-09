<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Antrean laporan (F13): sembunyikan barang, nonaktifkan akun, atau abaikan.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = ReportStatus::tryFrom($request->string('status')->toString()) ?? ReportStatus::Open;

        return view('admin.reports.index', [
            'status' => $status,
            'counts' => Report::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'reports' => Report::query()
                ->where('status', $status)
                ->with(['reporter', 'handler', 'reportable' => fn ($morph) => $morph->morphWith([Product::class => ['seller', 'cover']])])
                ->when($status === ReportStatus::Open, fn ($query) => $query->oldest(), fn ($query) => $query->latest('handled_at'))
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['hide_product', 'deactivate_user', 'dismiss'])],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($report->status === ReportStatus::Open, 422, 'Laporan ini sudah ditangani.');

        $target = $report->reportable;
        abort_if($target === null, 404);

        $action = $data['action'];
        $owner = $target instanceof Product ? $target->seller : $target;

        if ($action === 'deactivate_user' && $owner->isAdmin()) {
            return back()->withErrors(['report' => 'Akun admin tidak bisa dinonaktifkan dari laporan.']);
        }

        if ($action === 'hide_product' && ! $target instanceof Product) {
            return back()->withErrors(['report' => 'Laporan lapak tidak punya barang untuk disembunyikan.']);
        }

        $reporterIds = $target->reports()->where('status', ReportStatus::Open)->pluck('reporter_id')->unique();

        DB::transaction(function () use ($action, $target, $owner, $data, $request): void {
            match ($action) {
                'hide_product' => $target->forceFill(['status' => ProductStatus::Hidden, 'moderation_note' => $data['admin_note'] ?? 'Disembunyikan admin karena laporan pengguna.'])->save(),
                'deactivate_user' => $owner->forceFill(['is_active' => false])->save(),
                'dismiss' => null,
            };

            // Semua laporan terbuka untuk target yang sama ditutup dengan keputusan yang sama.
            $target->reports()->where('status', ReportStatus::Open)->update([
                'status' => $action === 'dismiss' ? ReportStatus::Dismissed : ReportStatus::Resolved,
                'handled_by' => $request->user()->id,
                'handled_at' => now(),
                'admin_note' => $data['admin_note'] ?? null,
            ]);
        });

        $this->notifyParties($reporterIds->all(), $action, $target, $owner);

        return back()->with('toast', match ($action) {
            'hide_product' => 'Barang disembunyikan dari katalog.',
            'deactivate_user' => "Akun {$owner->name} dinonaktifkan.",
            'dismiss' => 'Laporan diabaikan.',
        });
    }

    /**
     * @param  array<int, int>  $reporterIds
     */
    private function notifyParties(array $reporterIds, string $action, Product|User $target, User $owner): void
    {
        $label = $target instanceof Product ? "barang \"{$target->name}\"" : "lapak {$target->name}";

        foreach (User::query()->whereIn('id', $reporterIds)->get() as $reporter) {
            $reporter->notify(new TransactionUpdate(
                'Laporanmu sudah ditinjau',
                $action === 'dismiss'
                    ? "Admin meninjau laporan tentang {$label} dan tidak menemukan pelanggaran."
                    : "Admin menindaklanjuti laporan tentang {$label}. Terima kasih!",
                route('home'),
                $action === 'dismiss' ? 'info' : 'success',
            ));
        }

        if ($action === 'hide_product' && $target instanceof Product) {
            $owner->notify(new TransactionUpdate(
                'Barangmu disembunyikan admin',
                "\"{$target->name}\" disembunyikan dari katalog karena laporan pengguna. Hubungi admin bila ini keliru.",
                route('my-products.index', ['status' => ProductStatus::Hidden->value]),
                'danger',
            ));
        }
    }
}
