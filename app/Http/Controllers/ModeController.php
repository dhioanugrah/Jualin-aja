<?php

namespace App\Http\Controllers;

use App\Enums\UserMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mode Pembeli dan Mode Penjual (Proposal 2.2).
 */
class ModeController extends Controller
{
    public function create(): View
    {
        return view('mode.choose');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::enum(UserMode::class)],
        ]);

        $user = $request->user();
        $mode = UserMode::from($validated['mode']);

        if ($mode === UserMode::Seller && ! $user->canSell()) {
            return redirect()->route('profile.edit')
                ->with('toast', 'Isi nomor WhatsApp dulu sebelum masuk Mode Penjual.');
        }

        $user->update(['active_mode' => $mode]);

        return $mode === UserMode::Seller
            ? redirect()->route('seller.dashboard')->with('toast', 'Kamu sekarang di Mode Penjual.')
            : redirect()->route('home')->with('toast', 'Kamu sekarang di Mode Pembeli.');
    }
}
