<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\RedirectResponse;

/**
 * Tautan banner iklan lewat route ini supaya klik tercatat untuk laporan ke pengiklan.
 */
class AdClickController extends Controller
{
    public function __invoke(Ad $ad): RedirectResponse
    {
        abort_if(! $ad->isLive() || blank($ad->url), 404);

        $ad->increment('clicks');

        return redirect()->away($ad->url);
    }
}
