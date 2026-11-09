<x-guest-layout>
    <x-slot:title>Password baru</x-slot:title>

    <h1 class="text-3xl font-extrabold">Buat password baru</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="field-label">Email kampus</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username" class="field">
            <x-field-error name="email" />
        </div>
        <div>
            <label for="password" class="field-label">Password baru</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password" class="field">
            <x-field-error name="password" />
        </div>
        <div>
            <label for="password_confirmation" class="field-label">Ulangi password baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field">
            <x-field-error name="password_confirmation" />
        </div>
        <button class="btn-primary w-full py-3">Simpan password</button>
    </form>
</x-guest-layout>
