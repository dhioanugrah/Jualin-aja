<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tombol "Beri Masukan" (F16): skor 1–5 dan komentar, dipakai sebagai data uji pengguna (Bab 6).
 */
class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('feedback', [
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'page' => ['nullable', 'string', 'max:255'],
        ], [], ['score' => 'skor', 'comment' => 'komentar']);

        Feedback::query()->create([
            'user_id' => $request->user()?->id,
            'score' => $data['score'],
            'comment' => $data['comment'] ?? null,
            'page' => isset($data['page']) ? parse_url($data['page'], PHP_URL_PATH) : null,
        ]);

        return back()->with('toast', 'Terima kasih! Masukanmu membantu Jualin Aja ITK jadi lebih baik.');
    }
}
