<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CodArea;
use App\Http\Controllers\Controller;
use App\Http\Requests\CodLocationRequest;
use App\Models\CodLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Kelola titik COD Dalam ITK dan Sekitar ITK (Proposal 3.4).
 * Titik tidak dihapus karena dipakai riwayat booking; cukup dinonaktifkan.
 */
class CodLocationController extends Controller
{
    public function index(): View
    {
        return view('admin.cod-locations.index', [
            'locations' => CodLocation::query()->withCount('bookings')->orderBy('area')->orderBy('name')->get()->groupBy(fn (CodLocation $location): string => $location->area->value),
            'areas' => CodArea::cases(),
        ]);
    }

    public function store(CodLocationRequest $request): RedirectResponse
    {
        CodLocation::query()->create($request->validated() + ['is_active' => true]);

        return back()->with('toast', 'Titik COD ditambahkan.');
    }

    public function update(CodLocationRequest $request, CodLocation $codLocation): RedirectResponse
    {
        $codLocation->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return back()->with('toast', 'Titik COD diperbarui.');
    }
}
