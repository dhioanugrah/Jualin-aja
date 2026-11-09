<x-app-layout>
    <x-slot:title>Buku Panduan</x-slot:title>

    <section class="relative overflow-hidden rounded-3xl bg-brand-600 px-6 py-10 text-white sm:px-10">
        <div class="absolute -right-16 -top-16 size-48 rounded-full bg-accent-400"></div>
        <div class="relative max-w-xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold"><x-icon name="book-open" class="size-3.5" /> Buku Panduan</span>
            <h1 class="mt-4 text-3xl font-extrabold text-white sm:text-4xl">Cara pakai Jualin Aja ITK</h1>
            <p class="mt-2 text-brand-50">Bahasa santai, langkah pendek. Cari topik atau buka bab di bawah.</p>
            <form action="{{ route('guide.index') }}" method="GET" class="relative mt-6 max-w-md" role="search">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Contoh: kode serah terima, sanksi" aria-label="Cari di panduan"
                       class="h-12 w-full rounded-full border-0 bg-white pl-12 pr-24 text-sm text-ink shadow-float placeholder:text-slate-400 focus:ring-4 focus:ring-accent-300">
                <button class="btn-accent absolute right-1.5 top-1.5 h-9 px-4 py-0">Cari</button>
            </form>
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <a href="{{ route('guide.pdf') }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-4 py-2 font-semibold text-white backdrop-blur hover:bg-white/25"><x-icon name="download" class="size-4" /> Unduh versi PDF</a>
                <a href="{{ route('guide.print') }}" class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 font-semibold text-brand-50 hover:bg-white/10">Versi cetak</a>
            </div>
        </div>
    </section>

    @if ($results !== null)
        <section class="mt-8">
            <h2 class="mb-3 text-lg font-extrabold">Hasil untuk “{{ $search }}”</h2>
            @forelse ($results as $slug => $result)
                <a href="{{ route('guide.show', $slug) }}" class="card mb-2 block p-4 hover:border-brand-200">
                    <p class="flex items-center gap-2 font-bold text-ink"><x-icon :name="$result['icon']" class="size-4 text-brand-600" /> {{ $result['title'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $result['excerpt'] }}</p>
                </a>
            @empty
                <p class="card p-5 text-sm text-slate-500">Belum ketemu. Coba kata lain, atau buka bab Tanya Jawab.</p>
            @endforelse
        </section>
    @endif

    <section class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($chapters as $slug => $chapter)
            <a href="{{ route('guide.show', $slug) }}" class="card flex gap-4 p-5 transition hover:-translate-y-0.5 hover:border-brand-200">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon :name="$chapter['icon']" /></span>
                <div>
                    <p class="text-xs font-bold text-slate-400">Bab {{ $loop->iteration }}</p>
                    <h2 class="font-bold text-ink">{{ $chapter['title'] }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $chapter['summary'] }}</p>
                </div>
            </a>
        @endforeach
    </section>
</x-app-layout>
