<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tujuan setelah login/verifikasi: pilih mode dulu, lalu ke halaman awal mode itu.
 */
class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->active_mode === null) {
            return redirect()->route('mode.choose');
        }

        return $user->isSellerMode()
            ? redirect()->route('seller.dashboard')
            : redirect()->route('home');
    }
}
