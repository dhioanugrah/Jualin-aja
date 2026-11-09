@php
    $user = auth()->user();
    $isSeller = $user?->isSellerMode();
    $canSwitchMode = $user && $user->hasVerifiedEmail() && ! $user->isAdmin();
    $showBottomNav = $user && ! $user->isAdmin();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body @class(['min-h-dvh', 'pb-safe md:pb-0' => $showBottomNav])>
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>

    {{-- Header kaca, lengket di atas --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
            <a href="{{ $isSeller ? route('seller.dashboard') : route('home') }}" class="shrink-0" aria-label="Beranda Jualin Aja ITK">
                <span class="hidden sm:block"><x-logo /></span>
                <span class="block sm:hidden"><x-logo-mark class="size-9" /></span>
            </a>

            <div class="flex-1 md:hidden"></div>
            <form action="{{ route('catalog') }}" method="GET" class="relative hidden min-w-0 flex-1 md:mx-4 md:block" role="search">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari buku, kipas, jas lab…"
                       class="h-10 w-full rounded-full border-slate-200 bg-slate-100/70 pl-10 pr-4 text-sm placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-100">
            </form>

            <nav class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                @guest
                    <a href="{{ route('login') }}" class="btn-ghost px-4 py-2">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-primary hidden px-4 py-2 sm:inline-flex">Daftar</a>
                @else
                    @if ($canSwitchMode)
                        <form method="POST" action="{{ route('mode.update') }}" class="hidden rounded-full border border-slate-200 bg-slate-100/70 p-1 text-xs font-bold lg:flex">
                            @csrf @method('PATCH')
                            <button name="mode" value="buyer" @class(['rounded-full px-3 py-1.5 transition', 'bg-brand-600 text-white shadow-sm' => ! $isSeller, 'text-slate-500 hover:text-ink' => $isSeller])>Pembeli</button>
                            <button name="mode" value="seller" @class(['rounded-full px-3 py-1.5 transition', 'bg-brand-600 text-white shadow-sm' => $isSeller, 'text-slate-500 hover:text-ink' => ! $isSeller])>Penjual</button>
                        </form>
                    @endif

                    @if ($isSeller)
                        <a href="{{ route('products.create') }}" class="btn-accent hidden px-4 py-2 md:inline-flex"><x-icon name="plus" class="size-4" stroke="2.5" /> Jual</a>
                    @endif

                    @unless ($user->isAdmin())
                        <a href="{{ route('inbox') }}" class="hidden size-10 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 hover:text-ink md:inline-flex" aria-label="Pesan">
                            <x-icon name="inbox" />
                        </a>
                    @endunless

                    @if ($user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn-ghost hidden px-4 py-2 md:inline-flex"><x-icon name="shield-check" class="size-4" /> Panel Admin</a>
                    @endif

                    <a href="{{ route('notifications.index') }}" class="relative inline-flex size-10 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 hover:text-ink" aria-label="Notifikasi">
                        <x-icon name="bell" />
                        @if ($unreadNotificationsCount > 0)
                            <span class="absolute right-1.5 top-1.5 flex min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white">{{ min($unreadNotificationsCount, 9) }}{{ $unreadNotificationsCount > 9 ? '+' : '' }}</span>
                        @endif
                    </a>

                    <div x-data="{ open: false }" class="relative hidden md:block" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = ! open" class="flex items-center gap-1 rounded-full p-0.5 hover:bg-slate-100" :aria-expanded="open" aria-label="Menu akun">
                            <x-avatar :user="$user" />
                            <x-icon name="chevron-down" class="size-4 text-slate-400" />
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-float">
                            <div class="px-3 py-2.5">
                                <p class="truncate text-sm font-bold text-ink">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                            <div class="my-1 h-px bg-slate-100"></div>
                            @if ($isSeller)
                                <a href="{{ route('seller.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="store" class="size-4 text-slate-400" /> Dasbor Lapak</a>
                                <a href="{{ route('my-products.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="package" class="size-4 text-slate-400" /> Barang Saya</a>
                            @endif
                            @unless ($user->isAdmin())
                                <a href="{{ route('inbox') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="inbox" class="size-4 text-slate-400" /> Pesan & Booking</a>
                                <a href="{{ route('favorites.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="heart" class="size-4 text-slate-400" /> Favorit</a>
                                <a href="{{ route('sellers.show', $user) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="eye" class="size-4 text-slate-400" /> Lihat lapak publik</a>
                            @endunless
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="user" class="size-4 text-slate-400" /> Edit Profil</a>
                            <a href="{{ route('guide.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="book-open" class="size-4 text-slate-400" /> Buku Panduan</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-red-600 hover:bg-red-50"><x-icon name="log-out" class="size-4" /> Keluar</button>
                            </form>
                        </div>
                    </div>
                @endguest
            </nav>
        </div>
    </header>

    @if ($user && ! $user->hasVerifiedEmail())
        <div class="border-b border-accent-300/60 bg-accent-50">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2.5 text-sm text-ink">
                <x-icon name="info" class="size-4 text-accent-500" />
                <span>Verifikasi email kampus kamu dulu supaya bisa berjualan dan bertransaksi.</span>
                <a href="{{ route('verification.notice') }}" class="font-bold text-brand-600 underline-offset-2 hover:underline">Kirim ulang email verifikasi</a>
            </div>
        </div>
    @endif

    <main id="konten" class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        {{ $slot }}
    </main>

    <footer class="mt-10 hidden border-t border-slate-200 bg-white md:block">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-6 px-4 py-8">
            <div>
                <x-logo size="sm" />
                <p class="mt-2 max-w-sm text-sm text-slate-500">Jual-beli barang bekas antarmahasiswa Institut Teknologi Kalimantan. COD aman, penjual terverifikasi.</p>
            </div>
            <div class="flex flex-col items-end gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('guide.index') }}" class="btn-ghost px-4 py-2"><x-icon name="book-open" class="size-4" /> Buku Panduan</a>
                    <button type="button" @click="$dispatch('open-feedback')" x-data class="btn-ghost px-4 py-2"><x-icon name="smile" class="size-4" /> Beri Masukan</button>
                </div>
                <p class="text-xs text-slate-400">© {{ now()->year }} Jualin Aja ITK · Proyek mahasiswa, versi uji v0.3</p>
            </div>
        </div>
    </footer>

    {{-- Navigasi bawah HP: beda isi untuk Mode Pembeli dan Mode Penjual --}}
    @if ($showBottomNav)
        @php
            $navItems = $isSeller
                ? [
                    ['route' => 'seller.dashboard', 'icon' => 'store', 'label' => 'Lapak'],
                    ['route' => 'my-products.index', 'icon' => 'package', 'label' => 'Barang'],
                    ['route' => 'products.create', 'icon' => 'plus', 'label' => 'Jual', 'primary' => true],
                    ['route' => 'inbox', 'params' => ['tab' => 'masuk'], 'icon' => 'inbox', 'label' => 'Booking'],
                    ['route' => 'account', 'icon' => 'user', 'label' => 'Akun'],
                ]
                : [
                    ['route' => 'home', 'icon' => 'home', 'label' => 'Beranda'],
                    ['route' => 'catalog', 'icon' => 'search', 'label' => 'Katalog'],
                    ['route' => 'favorites.index', 'icon' => 'heart', 'label' => 'Favorit'],
                    ['route' => 'inbox', 'params' => ['tab' => 'keluar'], 'icon' => 'bag', 'label' => 'Pesanan'],
                    ['route' => 'account', 'icon' => 'user', 'label' => 'Akun', 'badge' => $unreadNotificationsCount],
                ];
        @endphp
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden" aria-label="Navigasi utama">
            <div class="mx-auto flex h-16 max-w-md items-stretch justify-around px-2">
                @foreach ($navItems as $item)
                    @php($active = request()->routeIs($item['route']))
                    @if ($item['primary'] ?? false)
                        <a href="{{ route($item['route']) }}" class="flex flex-1 flex-col items-center justify-center" aria-label="{{ $item['label'] }} barang">
                            <span class="-mt-7 flex size-14 items-center justify-center rounded-full bg-accent-400 text-ink shadow-[0_8px_20px_-6px_rgba(245,183,0,.7)] ring-4 ring-white transition active:scale-95">
                                <x-icon name="plus" class="size-6" stroke="2.5" />
                            </span>
                        </a>
                    @else
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}" @class(['relative flex flex-1 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold transition', 'text-brand-600' => $active, 'text-slate-400' => ! $active])>
                            <x-icon :name="$item['icon']" class="size-[22px]" :stroke="$active ? 2.4 : 2" />
                            {{ $item['label'] }}
                            @if (($item['badge'] ?? 0) > 0)
                                <span class="absolute right-[calc(50%-18px)] top-2 size-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </nav>
    @endif

    @include('partials.feedback-dialog')
    @include('partials.toast')
</body>
</html>
