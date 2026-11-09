<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\View\View;

/**
 * Masukan pengguna (F16) untuk evaluasi uji pengguna.
 */
class FeedbackController extends Controller
{
    public function index(): View
    {
        return view('admin.feedback.index', [
            'average' => Feedback::query()->avg('score'),
            'distribution' => Feedback::query()->selectRaw('score, count(*) as total')->groupBy('score')->pluck('total', 'score'),
            'total' => Feedback::query()->count(),
            'feedback' => Feedback::query()->with('user')->latest()->paginate(20),
        ]);
    }
}
