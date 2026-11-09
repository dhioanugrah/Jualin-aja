<x-app-layout>
    <x-slot:title>Akun</x-slot:title>

    <div class="mx-auto max-w-xl space-y-5">
        <div class="card flex items-center gap-4 p-5">
            <x-avatar :user="$user" size="size-16" />
            <div class="min-w-0">
                <h1 class="flex items-center gap-1 truncate text-xl font-extrabold">{{ $user->name }}
                    @if ($user->hasVerifiedEmail())<x-icon name="badge-check" class="size-5 text-brand-600" />@endif
                </h1>
                <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                @if ($user->nim)<p class="tabular text-xs text-slate-400">NIM {{ $user->nim }}</p>@endif
            </div>
        </div>

        @if ($user->hasVerifiedEmail() && ! $user->isAdmin())
            <div class="card p-5">
                <h2 class="flex items-center gap-2 font-bold"><x-icon name="repeat" class="size-4 text-brand-600" /> Ganti mode</h2>
                <p class="mt-1 text-sm text-slate-500">Mode menentukan menu dan halaman awal yang kamu lihat.</p>
                <form method="POST" action="{{ route('mode.update') }}" class="mt-4 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5">
                    @csrf @method('PATCH')
                    @foreach (\App\Enums\UserMode::cases() as $mode)
                        @php($active = $user->active_mode === $mode)
                        <button name="mode" value="{{ $mode->value }}" @class(['flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold transition', 'bg-white text-brand-700 shadow-card' => $active, 'text-slate-500 hover:text-ink' => ! $active])>
                            <x-icon :name="$mode === \App\Enums\UserMode::Seller ? 'store' : 'bag'" class="size-4" /> {{ $mode->label() }}
                        </button>
                    @endforeach
                </form>
            </div>
        @endif

        <nav class="card divide-y divide-slate-100 overflow-hidden">
            @if ($user->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="shield-check" class="text-slate-400" /> <span class="flex-1 font-semibold">Panel Admin</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
            @endif
            @if ($user->isSellerMode())
                <a href="{{ route('my-products.index') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="package" class="text-slate-400" /> <span class="flex-1 font-semibold">Barang Saya</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
            @endif
            @unless ($user->isAdmin())
                <a href="{{ route('inbox') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="inbox" class="text-slate-400" /> <span class="flex-1 font-semibold">Pesan & booking</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
                <a href="{{ route('favorites.index') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="heart" class="text-slate-400" /> <span class="flex-1 font-semibold">Favorit</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
                <a href="{{ route('sellers.show', $user) }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="store" class="text-slate-400" /> <span class="flex-1 font-semibold">Lapak publik saya</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
            @endunless
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="user" class="text-slate-400" /> <span class="flex-1 font-semibold">Edit profil</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
            <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="bell" class="text-slate-400" /> <span class="flex-1 font-semibold">Notifikasi</span>
                @if ($unreadCount = $user->unreadNotifications()->count())<span class="tabular rounded-full bg-red-500 px-2 text-xs font-bold leading-5 text-white">{{ $unreadCount }}</span>@endif<x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
            <a href="{{ route('guide.index') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50"><x-icon name="book-open" class="text-slate-400" /> <span class="flex-1 font-semibold">Buku panduan</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></a>
            <button type="button" x-data @click="$dispatch('open-feedback')" class="flex w-full items-center gap-3 px-5 py-4 text-left hover:bg-slate-50"><x-icon name="smile" class="text-slate-400" /> <span class="flex-1 font-semibold">Beri masukan</span><x-icon name="chevron-right" class="size-4 text-slate-300" /></button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-3 px-5 py-4 text-left text-red-600 hover:bg-red-50"><x-icon name="log-out" /> <span class="font-semibold">Keluar</span></button>
            </form>
        </nav>
    </div>
</x-app-layout>
