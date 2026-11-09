<x-app-layout>
    <x-slot:title>Katalog</x-slot:title>

    @php
        $tabs = ['barang' => ['label' => 'Barang', 'icon' => 'grid'], 'lapak' => ['label' => 'Lapak', 'icon' => 'store']];
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">
                @if ($search !== '')
                    Hasil untuk “{{ $search }}”
                @elseif ($tab === 'barang' && $filters['category'])
                    {{ $filters['category']->name }}
                @else
                    Katalog
                @endif
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $tab === 'barang' ? $products->total().' barang tersedia' : $sellers->total().' lapak aktif' }}
            </p>
        </div>

        <div class="inline-flex rounded-full border border-slate-200 bg-white p-1 text-sm font-bold" role="tablist">
            @foreach ($tabs as $key => $item)
                <a href="{{ route('catalog', array_filter(['tab' => $key === 'lapak' ? 'lapak' : null, 'q' => $search])) }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                   @class(['inline-flex items-center gap-1.5 rounded-full px-4 py-2 transition', 'bg-ink text-white' => $tab === $key, 'text-slate-500 hover:text-ink' => $tab !== $key])>
                    <x-icon :name="$item['icon']" class="size-4" /> {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($tab === 'lapak')
        <form action="{{ route('catalog') }}" method="GET" class="mb-5 flex flex-wrap gap-2">
            <input type="hidden" name="tab" value="lapak">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama penjual…" class="field h-11 rounded-full pl-12">
            </div>
            <select name="urut" class="field h-11 w-auto rounded-full pr-10" onchange="this.form.submit()" aria-label="Urutkan lapak">
                @foreach (\App\Http\Controllers\CatalogController::SELLER_SORTS as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        @if ($sellers->isEmpty())
            <x-empty-state icon="store" title="Belum ada lapak yang cocok" description="Coba nama lain atau lihat tab Barang." />
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sellers as $seller)
                    <x-seller-card :seller="$seller" />
                @endforeach
            </div>
            <div class="mt-8">{{ $sellers->links() }}</div>
        @endif
    @else
        @php
            $activeFilterCount = count($filters['conditions']) + ($filters['min'] !== null ? 1 : 0) + ($filters['max'] !== null ? 1 : 0) + ($filters['area'] ? 1 : 0);
        @endphp

        <form action="{{ route('catalog') }}" method="GET" x-data="{ open: {{ $activeFilterCount > 0 ? 'true' : 'false' }} }">
            @if ($filters['category'])
                <input type="hidden" name="kategori" value="{{ $filters['category']->slug }}">
            @endif

            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-0 flex-1 basis-60">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama barang…" class="field h-11 rounded-full pl-12" aria-label="Cari barang">
                </div>
                <select name="urut" class="field h-11 w-auto rounded-full pr-10" onchange="this.form.submit()" aria-label="Urutkan barang">
                    @foreach (\App\Http\Controllers\CatalogController::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" @click="open = ! open" class="btn-ghost h-11" :aria-expanded="open">
                    <x-icon name="filter" class="size-4" /> Filter
                    @if ($activeFilterCount > 0)
                        <span class="rounded-full bg-brand-600 px-1.5 text-[11px] font-bold leading-5 text-white">{{ $activeFilterCount }}</span>
                    @endif
                </button>
            </div>

            <div x-cloak x-show="open" x-transition class="card mt-3 grid gap-5 p-5 md:grid-cols-3">
                <fieldset>
                    <legend class="field-label">Kondisi</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($conditions as $condition)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="kondisi[]" value="{{ $condition->value }}" class="peer sr-only" @checked(in_array($condition, $filters['conditions'], true))>
                                <span class="chip peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">{{ $condition->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="field-label">Harga (Rp)</legend>
                    <div class="flex items-center gap-2">
                        <input type="number" name="harga_min" value="{{ $filters['min'] }}" min="0" step="1000" placeholder="Min" class="field tabular" aria-label="Harga minimum">
                        <span class="text-slate-400">–</span>
                        <input type="number" name="harga_max" value="{{ $filters['max'] }}" min="0" step="1000" placeholder="Maks" class="field tabular" aria-label="Harga maksimum">
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="field-label">Area COD</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([null => 'Semua', ...collect($codAreas)->mapWithKeys(fn ($area) => [$area->value => $area->label()])->all()] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="area" value="{{ $value }}" class="peer sr-only" @checked(($filters['area']?->value ?? '') === (string) $value)>
                                <span class="chip peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="flex gap-2 md:col-span-3">
                    <button class="btn-primary">Terapkan</button>
                    <a href="{{ route('catalog', array_filter(['kategori' => $filters['category']?->slug, 'q' => $search])) }}" class="btn-ghost">Hapus filter</a>
                </div>
            </div>
        </form>

        <div class="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-2 [scrollbar-width:none]">
            @php($baseQuery = request()->except(['kategori', 'page']))
            <a href="{{ route('catalog', $baseQuery) }}" class="{{ $filters['category'] ? 'chip' : 'chip-active' }}">Semua</a>
            @foreach ($categories as $category)
                <a href="{{ route('catalog', ['kategori' => $category->slug] + $baseQuery) }}" class="{{ $filters['category']?->is($category) ? 'chip-active' : 'chip' }}">
                    <x-icon :name="$category->icon" class="size-4" /> {{ $category->name }}
                </a>
            @endforeach
        </div>

        <section class="mt-4">
            @if ($products->isEmpty())
                <x-empty-state icon="search" title="Belum ada barang yang cocok" description="Coba kata kunci lain, hapus filter, atau lihat kategori berbeda.">
                    <x-slot:action><a href="{{ route('catalog') }}" class="btn-ghost">Reset pencarian</a></x-slot:action>
                </x-empty-state>
            @else
                @if ($featured->isNotEmpty())
                    <div class="mb-6 rounded-3xl bg-accent-50 p-4 ring-1 ring-accent-300/50">
                        <h2 class="mb-3 flex items-center gap-1.5 text-sm font-extrabold"><x-icon name="sparkles" class="size-4 text-accent-500" /> Unggulan{{ $filters['category'] ? ' di '.$filters['category']->name : '' }}</h2>
                        <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                            @foreach ($featured as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" class="animate-fade-up" style="animation-delay: {{ min($loop->index, 8) * 30 }}ms" />
                        @if ($loop->iteration === 8 && $inlineAd)
                            <x-ad-carousel :ads="collect([$inlineAd])" class="col-span-full" />
                        @endif
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        </section>
    @endif
</x-app-layout>
