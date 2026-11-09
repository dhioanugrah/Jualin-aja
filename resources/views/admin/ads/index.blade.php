<x-app-layout>
    <x-slot:title>Iklan</x-slot:title>

    <x-page-header title="Banner iklan" subtitle="Iklan UKM, himpunan, acara kampus, dan usaha mahasiswa. Pembayaran dicatat di luar sistem." />
    @include('admin._nav')

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="space-y-3">
            @forelse ($ads as $ad)
                <article @class(['card flex flex-col gap-4 overflow-hidden p-3 sm:flex-row sm:items-center', 'opacity-60' => ! $ad->isLive()])>
                    <img src="{{ $ad->image_url }}" alt="" class="aspect-[16/5] w-full shrink-0 rounded-xl object-cover sm:w-56">
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="truncate font-bold text-ink">{{ $ad->title }}</span>
                            <span @class(['rounded-full px-2 py-0.5 text-[11px] font-bold', 'bg-emerald-50 text-emerald-700' => $ad->isLive(), 'bg-slate-100 text-slate-500' => ! $ad->isLive()])>{{ $ad->isLive() ? 'Tayang' : ($ad->is_active ? ($ad->starts_at->isFuture() ? 'Terjadwal' : 'Selesai') : 'Nonaktif') }}</span>
                        </p>
                        <p class="text-xs text-slate-500">{{ $ad->advertiser }} · {{ $ad->placement->label() }} · {{ $ad->starts_at->translatedFormat('j M') }}–{{ $ad->ends_at->translatedFormat('j M Y') }}</p>
                        <p class="tabular mt-1 text-sm text-slate-700"><strong>{{ number_format($ad->impressions, 0, ',', '.') }}</strong> tayang · <strong>{{ number_format($ad->clicks, 0, ',', '.') }}</strong> klik · CTR {{ number_format($ad->ctr, 1, ',', '') }}%</p>
                    </div>
                    <a href="{{ route('admin.ads.edit', $ad) }}" class="btn-ghost shrink-0 px-3.5 py-2"><x-icon name="pencil" class="size-4" /> Ubah</a>
                </article>
            @empty
                <x-empty-state icon="megaphone" title="Belum ada iklan" description="Tambahkan banner pertama lewat form di samping." />
            @endforelse
        </section>

        <aside>
            <form method="POST" action="{{ route('admin.ads.store') }}" enctype="multipart/form-data" class="card space-y-4 p-5 lg:sticky lg:top-24">
                @csrf
                <h2 class="font-extrabold">Tambah iklan</h2>
                @include('admin.ads._form', ['ad' => new \App\Models\Ad])
                <button class="btn-primary w-full">Simpan iklan</button>
            </form>
        </aside>
    </div>
</x-app-layout>
