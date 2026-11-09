<section class="card p-5 sm:p-6">
    <h2 class="font-bold">Informasi profil</h2>
    <p class="text-sm text-slate-500">Nama, foto, dan prodi tampil di lapakmu. Nomor WhatsApp tidak pernah ditampilkan sebagai teks.</p>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5"
          x-data="{ preview: null }">
        @csrf @method('PATCH')

        <div class="flex items-center gap-4">
            <template x-if="preview"><img :src="preview" alt="" class="size-16 rounded-full object-cover ring-2 ring-white"></template>
            <div x-show="! preview"><x-avatar :user="$user" size="size-16" /></div>
            <label class="btn-ghost cursor-pointer px-4 py-2">
                <x-icon name="camera" class="size-4" /> Ganti foto
                <input type="file" name="avatar" accept="image/*" class="sr-only" @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
            </label>
        </div>
        <x-field-error name="avatar" />

        <div>
            <label for="name" class="field-label">Nama lengkap</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="field">
            <x-field-error name="name" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="field-label">Email kampus</label>
                <input value="{{ $user->email }}" disabled class="field bg-slate-50 text-slate-500">
            </div>
            <div>
                <label class="field-label">NIM</label>
                <input value="{{ $user->nim ?? '–' }}" disabled class="field tabular bg-slate-50 text-slate-500">
            </div>
        </div>

        <div>
            <label for="whatsapp" class="field-label">Nomor WhatsApp</label>
            <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" required inputmode="tel" class="field tabular" placeholder="0812xxxxxxxx">
            <x-field-error name="whatsapp" />
        </div>

        <div class="grid gap-5 sm:grid-cols-[1fr_140px]">
            <div>
                <label for="prodi" class="field-label">Program studi</label>
                <input id="prodi" name="prodi" value="{{ old('prodi', $user->prodi) }}" class="field" placeholder="Contoh: Informatika">
                <x-field-error name="prodi" />
            </div>
            <div>
                <label for="angkatan" class="field-label">Angkatan</label>
                <input id="angkatan" name="angkatan" inputmode="numeric" value="{{ old('angkatan', $user->angkatan) }}" class="field tabular" placeholder="2023">
                <x-field-error name="angkatan" />
            </div>
        </div>

        <button class="btn-primary">Simpan profil</button>
    </form>
</section>
