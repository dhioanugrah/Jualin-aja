<x-guest-layout>
    <x-slot:title>Masuk</x-slot:title>

    <h1 class="text-3xl font-extrabold">Selamat datang lagi 👋</h1>
    <p class="mt-2 text-slate-500">Masuk dengan email kampus untuk jual-beli di Jualin Aja ITK.</p>

    @if (session('status'))
        <div class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="field-label">Email kampus</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field" placeholder="{{ 'nim@'.config('jualin.email_domain') }}">
            <x-field-error name="email" />
        </div>

        <div x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="field-label">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-semibold text-brand-600 hover:underline">Lupa password?</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" class="field pr-12">
                <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 hover:text-ink" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                    <x-icon name="eye" class="size-4" />
                </button>
            </div>
            <x-field-error name="password" />
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            Ingat saya
        </label>

        <button class="btn-primary w-full py-3">Masuk</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:underline">Daftar sekarang</a>
    </p>
</x-guest-layout>
