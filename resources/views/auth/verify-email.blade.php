<x-guest-layout>
    <x-slot:title>Verifikasi email</x-slot:title>

    <div class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"><x-icon name="inbox" class="size-7" /></div>
    <h1 class="mt-6 text-3xl font-extrabold">Cek email kampusmu</h1>
    <p class="mt-2 text-slate-500">
        Kami mengirim link verifikasi ke <span class="font-semibold text-ink">{{ auth()->user()->email }}</span>.
        Klik link itu untuk mulai berjualan dan bertransaksi. Sambil menunggu, kamu tetap bisa melihat-lihat barang.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">Link verifikasi baru sudah dikirim.</div>
    @endif

    <div class="mt-8 flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn-primary">Kirim ulang link</button>
        </form>
        <a href="{{ route('home') }}" class="btn-ghost">Lihat-lihat dulu</a>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
            @csrf
            <button class="text-sm font-semibold text-slate-500 hover:text-ink">Keluar</button>
        </form>
    </div>
</x-guest-layout>
