<section class="card border-red-100 p-5 sm:p-6" x-data="{ confirming: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <h2 class="font-bold text-red-700">Hapus akun</h2>
    <p class="text-sm text-slate-500">Semua barang dan riwayat transaksi kamu ikut terhapus permanen.</p>

    <button type="button" x-show="! confirming" @click="confirming = true" class="btn-ghost mt-4 text-red-600">Hapus akun saya</button>

    <form x-show="confirming" x-cloak method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-3">
        @csrf @method('DELETE')
        <label for="delete_password" class="field-label">Masukkan password untuk konfirmasi</label>
        <input id="delete_password" name="password" type="password" class="field">
        @foreach ($errors->userDeletion->get('password') as $message)
            <p class="text-xs font-medium text-red-600">{{ $message }}</p>
        @endforeach
        <div class="flex gap-2">
            <button type="button" @click="confirming = false" class="btn-ghost">Batal</button>
            <button class="btn-danger">Hapus permanen</button>
        </div>
    </form>
</section>
