<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdRequest;
use App\Models\Ad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Kelola banner iklan (F15): UKM, himpunan, acara kampus, dan usaha mahasiswa.
 * Pembayaran dicatat manual oleh admin; tayang dan klik dihitung untuk laporan ke pengiklan.
 */
class AdController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Ad::class);

        return view('admin.ads.index', [
            'ads' => Ad::query()->latest('starts_at')->get(),
            'placements' => AdPlacement::cases(),
        ]);
    }

    public function store(AdRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $request->file('image')->store('ads', 'public');
        $data['is_active'] = $request->boolean('is_active', true);

        Ad::query()->create($data);

        return back()->with('toast', 'Iklan ditambahkan.');
    }

    public function edit(Ad $ad): View
    {
        Gate::authorize('update', $ad);

        return view('admin.ads.edit', ['ad' => $ad, 'placements' => AdPlacement::cases()]);
    }

    public function update(AdRequest $request, Ad $ad): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $old = $ad->image;
            $data['image'] = $request->file('image')->store('ads', 'public');
            Storage::disk('public')->delete($old);
        }

        $ad->update($data);

        return redirect()->route('admin.ads.index')->with('toast', 'Iklan diperbarui.');
    }
}
