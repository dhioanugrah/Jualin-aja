<x-app-layout>
    <x-slot:title>Masukan</x-slot:title>

    <x-page-header title="Masukan pengguna" subtitle="Skor 1–5 dari tombol Beri Masukan. Dipakai sebagai data uji pengguna (Bab 6)." />
    @include('admin._nav')

    <div class="grid gap-6 lg:grid-cols-[300px_1fr]">
        <aside class="card h-fit p-5">
            <p class="text-xs font-semibold text-slate-500">Rata-rata skor</p>
            <p class="tabular mt-1 text-4xl font-extrabold text-ink">{{ $total > 0 ? number_format($average, 1, ',', '') : '–' }}<span class="text-lg text-slate-400"> / 5</span></p>
            <p class="text-sm text-slate-500">dari {{ $total }} masukan</p>
            <ul class="mt-5 space-y-2">
                @for ($score = 5; $score >= 1; $score--)
                    @php($count = (int) ($distribution[$score] ?? 0))
                    <li class="flex items-center gap-2 text-sm">
                        <span class="tabular w-3 font-semibold text-slate-600">{{ $score }}</span>
                        <span class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-brand-600" style="width: {{ $total > 0 ? round($count / $total * 100) : 0 }}%"></span></span>
                        <span class="tabular w-6 text-right text-xs text-slate-500">{{ $count }}</span>
                    </li>
                @endfor
            </ul>
        </aside>

        <section>
            @if ($feedback->isEmpty())
                <x-empty-state icon="smile" title="Belum ada masukan" description="Masukan muncul di sini setelah pengguna menekan tombol Beri Masukan." />
            @else
                <ul class="card divide-y divide-slate-100">
                    @foreach ($feedback as $item)
                        <li class="flex gap-4 p-4">
                            <span class="tabular flex size-10 shrink-0 items-center justify-center rounded-xl text-lg font-extrabold {{ $item->score >= 4 ? 'bg-emerald-50 text-emerald-700' : ($item->score === 3 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $item->score }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-700">{{ $item->comment ?: 'Tanpa komentar.' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $item->user?->name ?? 'Tamu' }} · /{{ ltrim($item->page ?? '', '/') }} · {{ $item->created_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $feedback->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
