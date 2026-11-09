<x-app-layout>
    <x-slot:title>Dasbor Lapak</x-slot:title>

    @php($user = auth()->user())

    <section class="relative overflow-hidden rounded-3xl bg-ink p-6 text-white sm:p-8">
        <div class="absolute -right-16 -top-20 size-44 rounded-full bg-accent-400/90 sm:size-40"></div>
        <div class="relative flex flex-wrap items-center gap-4">
            <x-avatar :user="$user" size="size-14" />
            <div class="flex-1">
                <p class="text-sm text-slate-300">Lapak milik</p>
                <h1 class="text-2xl font-extrabold text-white">{{ $user->name }}</h1>
            </div>
            <a href="{{ route('products.create') }}" class="btn-accent relative"><x-icon name="plus" class="size-4" stroke="2.5" /> Jual Barang</a>
        </div>

        <dl class="relative mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['label' => 'Tayang', 'value' => $stats['active'], 'icon' => 'store'],
                ['label' => 'Menunggu moderasi', 'value' => $stats['pending'], 'icon' => 'clock'],
                ['label' => 'Terjual', 'value' => $stats['sold'], 'icon' => 'check'],
                ['label' => 'Total dilihat', 'value' => $stats['views'], 'icon' => 'eye'],
            ] as $stat)
                <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                    <dt class="flex items-center gap-1.5 text-xs text-slate-300"><x-icon :name="$stat['icon']" class="size-3.5" /> {{ $stat['label'] }}</dt>
                    <dd class="tabular mt-1 text-3xl font-extrabold text-white">{{ number_format($stat['value'], 0, ',', '.') }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="card flex gap-4 p-5">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-500"><x-icon name="sparkles" /></span>
            <div class="flex-1">
                <h3 class="font-bold">Promosi gratis</h3>
                <p class="mt-0.5 text-sm text-slate-500">
                    @if ($stats['promotionQuota'] > 0)
                        Sisa kuota <strong class="text-ink">{{ $stats['promotionQuota'] }}</strong> minggu ini.
                    @else
                        Kuota berikutnya {{ $nextPromotionAt?->translatedFormat('j M H:i') }}.
                    @endif
                    {{ $stats['activePromotions'] }} barang sedang jadi Unggulan.
                </p>
                <a href="{{ route('my-products.index', ['status' => 'approved']) }}" class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:underline">Pilih barang untuk dipromosikan</a>
            </div>
        </div>
        <div class="card flex gap-4 p-5">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="chart" /></span>
            <div class="flex-1">
                <h3 class="font-bold">Statistik barang</h3>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ number_format($stats['views'], 0, ',', '.') }} kali dilihat · {{ $stats['pendingOffers'] }} penawaran menunggu · {{ $stats['upcomingBookings'] }} COD terjadwal.
                </p>
                <a href="{{ route('sellers.show', $user) }}" class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:underline">Lihat lapak publik</a>
            </div>
        </div>
    </section>

    @if ($needsAttention->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-extrabold"><span class="size-2 rounded-full bg-red-500"></span> Perlu kamu perbaiki</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($needsAttention as $product)
                    <a href="{{ route('products.edit', $product) }}" class="card flex items-center gap-3 p-3 hover:border-red-200">
                        <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            @if ($product->cover_url)<img src="{{ $product->cover_url }}" alt="" class="size-full object-cover">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-ink">{{ $product->name }}</p>
                            <p class="line-clamp-2 text-xs text-red-700">{{ $product->moderation_note }}</p>
                        </div>
                        <x-icon name="chevron-right" class="text-slate-300" />
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between">
            <h2 class="text-lg font-extrabold">Barang terakhir diubah</h2>
            <a href="{{ route('my-products.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua</a>
        </div>

        @if ($recent->isEmpty())
            <x-empty-state icon="camera" title="Lapakmu masih kosong" description="Mulai dengan memotret satu barang. Cuma butuh satu menit.">
                <x-slot:action><a href="{{ route('products.create') }}" class="btn-accent">Jual barang pertama</a></x-slot:action>
            </x-empty-state>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3">
                @foreach ($recent as $product)
                    <x-product-card :product="$product" show-status />
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-8 grid gap-3 sm:grid-cols-2">
        <a href="{{ route('inbox', ['tab' => 'masuk']) }}" class="card flex gap-4 p-5 transition hover:border-brand-200">
            <span class="relative flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <x-icon name="inbox" />
                @if ($stats['pendingBookings'] + $stats['pendingOffers'] > 0)
                    <span class="absolute -right-1 -top-1 size-3 rounded-full bg-red-500 ring-2 ring-white"></span>
                @endif
            </span>
            <div class="flex-1">
                <h3 class="font-bold">Booking & penawaran masuk</h3>
                <p class="text-sm text-slate-500">
                    <span class="tabular font-semibold text-ink">{{ $stats['pendingBookings'] }}</span> booking menunggu respons ·
                    <span class="tabular font-semibold text-ink">{{ $stats['upcomingBookings'] }}</span> COD terjadwal ·
                    <span class="tabular font-semibold text-ink">{{ $stats['pendingOffers'] }}</span> penawaran
                </p>
            </div>
            <x-icon name="chevron-right" class="size-4 self-center text-slate-300" />
        </a>
        <div class="card flex gap-4 p-5 opacity-80">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-500"><x-icon name="sparkles" /></span>
            <div>
                <h3 class="font-bold">Promosikan barang</h3>
                <p class="text-sm text-slate-500">Kuota promosi gratis 1× seminggu ke slot Unggulan (Sprint 3).</p>
            </div>
        </div>
    </section>
</x-app-layout>
