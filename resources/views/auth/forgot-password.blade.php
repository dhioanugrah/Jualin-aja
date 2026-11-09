<x-guest-layout>
    <x-slot:title>Lupa password</x-slot:title>

    <h1 class="text-3xl font-extrabold">Lupa password?</h1>
    <p class="mt-2 text-slate-500">Masukkan email kampusmu, kami kirim link untuk membuat password baru.</p>

    @if (session('status'))
        <div class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="field-label">Email kampus</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="field">
            <x-field-error name="email" />
        </div>
        <button class="btn-primary w-full py-3">Kirim link reset</button>
    </form>

    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="font-bold text-brand-600 hover:underline">Kembali ke halaman masuk</a></p>
</x-guest-layout>
