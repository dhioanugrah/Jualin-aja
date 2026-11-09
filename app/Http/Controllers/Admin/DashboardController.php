<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ProductStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\ViolationStatus;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Booking;
use App\Models\Feedback;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard admin: pengguna, barang dan booking per status, pelanggaran,
 * tayang/klik iklan, dan rata-rata masukan.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $ads = Ad::query()->selectRaw('coalesce(sum(impressions), 0) as impressions, coalesce(sum(clicks), 0) as clicks')->first();

        return view('admin.dashboard', [
            'userCount' => User::query()->where('role', UserRole::Mahasiswa)->count(),
            'productCounts' => $this->countByStatus(Product::class, ProductStatus::cases()),
            'bookingCounts' => $this->countByStatus(Booking::class, BookingStatus::cases()),
            'activeViolations' => Violation::query()->where('status', ViolationStatus::Active)->count(),
            'openReports' => Report::query()->where('status', ReportStatus::Open)->count(),
            'openDisputes' => Booking::query()->where('status', BookingStatus::Disputed)->count(),
            'ads' => [
                'live' => Ad::query()->live()->count(),
                'impressions' => (int) $ads->impressions,
                'clicks' => (int) $ads->clicks,
            ],
            'feedback' => [
                'average' => Feedback::query()->avg('score'),
                'count' => Feedback::query()->count(),
            ],
            'queue' => Product::query()
                ->where('status', ProductStatus::Pending)
                ->with(['cover', 'seller'])
                ->oldest('updated_at')
                ->take(5)
                ->get(),
        ]);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, \BackedEnum>  $cases
     * @return Collection<string, int>
     */
    private function countByStatus(string $model, array $cases): Collection
    {
        $counts = $model::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect($cases)->mapWithKeys(fn (\BackedEnum $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)]);
    }
}
