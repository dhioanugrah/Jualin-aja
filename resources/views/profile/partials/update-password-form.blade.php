<section class="card p-5 sm:p-6">
    <h2 class="font-bold">Ganti password</h2>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf @method('PUT')

        <div>
            <label for="current_password" class="field-label">Password sekarang</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="field">
            @foreach ($errors->updatePassword->get('current_password') as $message)
                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
            @endforeach
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Password baru</label>
                <input id="password" name="password" type="password" autocomplete="new-password" class="field">
                @foreach ($errors->updatePassword->get('password') as $message)
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @endforeach
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Ulangi password baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="field">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button class="btn-ghost">Simpan password</button>
            @if (session('status') === 'password-updated')
                <p class="text-sm font-medium text-emerald-600">Password tersimpan.</p>
            @endif
        </div>
    </form>
</section>
