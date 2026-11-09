<x-guest-layout>
    <x-slot:title>Daftar</x-slot:title>

    <h1 class="text-3xl font-extrabold">Buat akun mahasiswa</h1>
    <p class="mt-2 text-slate-500">Khusus mahasiswa ITK. Kami akan mengirim link verifikasi ke email kampusmu.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="name" class="field-label">Nama lengkap</label>
            <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="field">
            <x-field-error name="name" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="nim" class="field-label">NIM</label>
                <input id="nim" name="nim" value="{{ old('nim') }}" required inputmode="numeric" class="field tabular" placeholder="11221001">
                <x-field-error name="nim" />
            </div>
            <div>
                <label for="whatsapp" class="field-label">Nomor WhatsApp</label>
                <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required inputmode="tel" autocomplete="tel" class="field tabular" placeholder="0812xxxxxxxx">
                <x-field-error name="whatsapp" />
            </div>
        </div>

        <div>
            <label for="email" class="field-label">Email kampus</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="field" placeholder="{{ 'nim@'.config('jualin.email_domain') }}">
            <p class="mt-1.5 text-xs text-slate-500">Harus berakhiran <span class="font-semibold">{{ '@'.config('jualin.email_domain') }}</span>.</p>
            <x-field-error name="email" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="field">
                <x-field-error name="password" />
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Ulangi password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field">
            </div>
        </div>

        <div class="flex gap-3 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
            <x-icon name="shield-check" class="size-4 text-brand-600" />
            <p>Nomor WhatsApp tidak pernah ditampilkan sebagai teks di web. Pembeli hanya bisa menghubungimu lewat tombol yang tercatat.</p>
        </div>

        <button class="btn-primary w-full py-3">Daftar</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:underline">Masuk</a>
    </p>
</x-guest-layout>
