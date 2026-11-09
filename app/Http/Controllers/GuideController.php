<?php

namespace App\Http\Controllers;

use App\Support\Guide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Buku Panduan (F14): bisa dicari dan dibuka tanpa login.
 */
class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $isAdmin = (bool) $request->user()?->isAdmin();
        $search = trim($request->string('q')->limit(60, '')->toString());

        return view('guide.index', [
            'chapters' => Guide::chapters($isAdmin),
            'search' => $search,
            'results' => $search !== '' ? Guide::search($search, $isAdmin) : null,
        ]);
    }

    public function show(Request $request, string $chapter): View
    {
        $chapters = Guide::chapters((bool) $request->user()?->isAdmin());
        abort_unless($chapters->has($chapter), 404);

        $slugs = $chapters->keys();
        $index = $slugs->search($chapter);

        return view('guide.show', [
            'slug' => $chapter,
            'chapter' => $chapters[$chapter],
            'chapters' => $chapters,
            'previous' => $index > 0 ? $slugs[$index - 1] : null,
            'next' => $slugs[$index + 1] ?? null,
        ]);
    }

    /**
     * Versi cetak: bisa disimpan sebagai PDF lewat browser, dan dipakai perintah guide:pdf.
     */
    public function print(Request $request): View
    {
        $isAdmin = (bool) $request->user()?->isAdmin();

        return view('guide.print', [
            'chapters' => Guide::chapters($isAdmin),
            'forAdmin' => $isAdmin,
            'forFile' => false,
        ]);
    }

    /**
     * Unduh PDF yang dibuat perintah guide:pdf. Admin mendapat versi dengan bab Admin.
     * Bila file belum dibuat, arahkan ke versi cetak.
     */
    public function pdf(Request $request): StreamedResponse|RedirectResponse
    {
        $path = Guide::pdfPath((bool) $request->user()?->isAdmin());

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('guide.print');
        }

        return Storage::disk('local')->response($path, 'Buku Panduan Jualin Aja ITK.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, max-age=3600',
            'Vary' => 'Cookie',
        ], 'inline');
    }
}
