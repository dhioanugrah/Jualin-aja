<x-guest-layout>
    <x-slot:title>Konfirmasi password</x-slot:title>

    <h1 class="text-3xl font-extrabold">Konfirmasi password</h1>
    <p class="mt-2 text-slate-500">Ini area aman. Masukkan password kamu untuk melanjutkan.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="password" class="field-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="field">
            <x-field-error name="password" />
        </div>
        <button class="btn-primary w-full py-3">Lanjutkan</button>
    </form>
</x-guest-layout>
