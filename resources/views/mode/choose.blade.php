<x-app-layout>
    <x-slot:title>Mau mulai sebagai apa?</x-slot:title>

    <div class="mx-auto max-w-3xl py-4 sm:py-10">
        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200"><x-icon name="badge-check" class="size-3.5" /> Email terverifikasi</span>
            <h1 class="mt-4 text-3xl font-extrabold sm:text-4xl">Mau mulai sebagai apa, {{ str(auth()->user()->name)->before(' ') }}?</h1>
            <p class="mx-auto mt-3 max-w-xl text-slate-500">Satu akun bisa jadi pembeli sekaligus penjual. Pilih tampilan yang kamu butuhkan sekarang, nanti bisa diganti kapan saja lewat menu Akun.</p>
        </div>

        <form method="POST" action="{{ route('mode.update') }}" class="mt-10 grid gap-4 sm:grid-cols-2">
            @csrf @method('PATCH')

            <button name="mode" value="buyer" class="group card relative flex flex-col items-start overflow-hidden p-6 text-left transition hover:-translate-y-1 hover:border-brand-300 hover:shadow-float focus-visible:outline-2 focus-visible:outline-brand-600">
                <div class="absolute -right-10 -top-10 size-32 rounded-full bg-brand-50 transition group-hover:scale-110"></div>
                <span class="relative flex size-12 items-center justify-center rounded-2xl bg-brand-600 text-white"><x-icon name="bag" class="size-6" /></span>
                <h2 class="relative mt-5 text-xl font-extrabold">Aku mau beli</h2>
                <p class="relative mt-1 text-sm text-slate-500">Cari barang per jenis atau per lapak, simpan favorit, lalu ajukan COD.</p>
                <span class="relative mt-6 inline-flex items-center gap-1 text-sm font-bold text-brand-600">Masuk Mode Pembeli <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" /></span>
            </button>

            <button name="mode" value="seller" class="group card relative flex flex-col items-start overflow-hidden p-6 text-left transition hover:-translate-y-1 hover:border-accent-300 hover:shadow-float focus-visible:outline-2 focus-visible:outline-brand-600">
                <div class="absolute -right-10 -top-10 size-32 rounded-full bg-accent-50 transition group-hover:scale-110"></div>
                <span class="relative flex size-12 items-center justify-center rounded-2xl bg-accent-400 text-ink"><x-icon name="store" class="size-6" /></span>
                <h2 class="relative mt-5 text-xl font-extrabold">Aku mau jual</h2>
                <p class="relative mt-1 text-sm text-slate-500">Foto barang langsung dari kamera, atur lapakmu, dan terima booking COD.</p>
                <span class="relative mt-6 inline-flex items-center gap-1 text-sm font-bold text-ink">Masuk Mode Penjual <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" /></span>
            </button>
        </form>

        {{-- Tur singkat 3 langkah (F1) yang menautkan ke Buku Panduan --}}
        <ol class="mt-10 grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['icon' => 'search', 'title' => 'Cari atau jual', 'text' => 'Cari per jenis atau per lapak. Penjual memotret barang lalu menunggu moderasi admin.', 'chapter' => 'mulai'],
                ['icon' => 'calendar', 'title' => 'Kunci di web', 'text' => 'Diskusi boleh lewat WA, tapi booking COD dikunci di web dan dikonfirmasi H-3 jam.', 'chapter' => 'aturan'],
                ['icon' => 'key', 'title' => 'Selesai dengan kode', 'text' => 'Saat bertemu, pembeli menyebutkan kode 4 digit. Lalu saling beri rating.', 'chapter' => 'cod-aman'],
            ] as $step)
                <li class="card flex gap-3 p-4">
                    <span class="tabular flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-extrabold text-brand-600">{{ $loop->iteration }}</span>
                    <div>
                        <h3 class="font-bold">{{ $step['title'] }}</h3>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $step['text'] }}</p>
                        <a href="{{ route('guide.show', $step['chapter']) }}" class="mt-1 inline-block text-xs font-semibold text-brand-600 hover:underline">Baca panduan</a>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-app-layout>
