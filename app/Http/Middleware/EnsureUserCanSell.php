<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fitur penjual butuh nomor WhatsApp di profil.
 */
class EnsureUserCanSell
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canSell()) {
            return redirect()->route('profile.edit')
                ->with('toast', 'Isi nomor WhatsApp dulu sebelum mulai berjualan.');
        }

        return $next($request);
    }
}
