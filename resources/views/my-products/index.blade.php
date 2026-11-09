<x-app-layout>
    <x-slot:title>Barang Saya</x-slot:title>

    <x-page-header title="Barang Saya" subtitle="Kelola semua barang yang kamu jual.">
        <x-slot:actions>
            <a href="{{ route('products.create') }}" class="btn-accent"><x-icon name="plus" class="size-4" stroke="2.5" /> Jual Barang</a>
        </x-slot:actions>
    </x-page-header>

    <nav class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" aria-label="Filter status">
        <a href="{{ route('my-products.index') }}" class="{{ $activeStatus ? 'chip' : 'chip-active' }}">Semua <span class="opacity-60">{{ $total }}</span></a>
        @foreach (\App\Enums\ProductStatus::cases() as $status)
            <a href="{{ route('my-products.index', ['status' => $status->value]) }}" class="{{ $activeStatus === $status ? 'chip-active' : 'chip' }}">
                {{ $status->label() }} <span class="opacity-60">{{ $counts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-accent-300/60 bg-accent-50 px-4 py-3 text-sm">
        <x-icon name="sparkles" class="size-4 text-accent-500" />
        <span class="flex-1">
            @if ($promotionQuota > 0)
                Kuota promosi gratis: <strong>{{ $promotionQuota }}</strong> minggu ini. Barang yang dipromosikan tampil di Barang Unggulan selama {{ config('jualin.promotion_days') }} hari.
            @else
                Kuota promosi minggu ini sudah terpakai. Kuota berikutnya <strong>{{ $nextPromotionAt?->translatedFormat('j M H:i') }}</strong>.
            @endif
        </span>
        <a href="{{ route('guide.show', 'promosi') }}" class="font-semibold text-brand-600 hover:underline">Cara kerja</a>
    </div>
    @error('promotion')<p class="mb-4 rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror

    @if ($products->isEmpty())
        <x-empty-state icon="package" title="Belum ada barang di sini" description="Foto barang yang sudah tidak kamu pakai, lalu jual ke adik tingkat.">
            <x-slot:action><a href="{{ route('products.create') }}" class="btn-accent">Jual barang pertama</a></x-slot:action>
        </x-empty-state>
    @else
        <ul class="grid gap-3">
            @foreach ($products as $product)
                <li class="card flex flex-col gap-4 p-3 sm:flex-row sm:items-center sm:p-4">
                    <a href="{{ route('products.show', $product) }}" class="flex min-w-0 flex-1 items-center gap-4">
                        <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:size-24">
                            @if ($product->cover_url)
                                <img src="{{ $product->cover_url }}" alt="" class="size-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0">
                            <x-status-badge :status="$product->status" />
                            @if ($product->getAttribute('is_promoted'))
                                <span class="ml-1 inline-flex items-center gap-0.5 rounded-full bg-accent-400 px-2 py-0.5 text-[11px] font-bold text-ink"><x-icon name="sparkles" class="size-3" /> Unggulan</span>
                            @endif
                            <h3 class="mt-1 truncate font-bold text-ink">{{ $product->name }}</h3>
                            <p class="tabular text-sm font-extrabold text-brand-600">{{ $product->formatted_price }}</p>
                            <p class="mt-0.5 flex items-center gap-3 text-xs text-slate-500">
                                <span>{{ $product->category->name }}</span>
                                <span class="inline-flex items-center gap-1"><x-icon name="eye" class="size-3.5" /> {{ $product->views }}</span>
                                <span>Diubah {{ $product->updated_at->diffForHumans() }}</span>
                            </p>
                        </div>
                    </a>

                    @if (in_array($product->status, [\App\Enums\ProductStatus::NeedsRevision, \App\Enums\ProductStatus::Hidden], true) && $product->moderation_note)
                        <div class="rounded-xl bg-red-50 p-3 text-xs text-red-800 sm:max-w-xs">
                            <span class="font-bold">Catatan admin:</span> {{ $product->moderation_note }}
                        </div>
                    @endif

                    <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                        @can('update', $product)
                            <a href="{{ route('products.edit', $product) }}" class="btn-ghost px-3.5 py-2"><x-icon name="pencil" class="size-4" /> Edit</a>
                        @endcan
                        @if ($product->status === \App\Enums\ProductStatus::Approved && ! $product->getAttribute('is_promoted') && $promotionQuota > 0)
                            <form method="POST" action="{{ route('products.promote', $product) }}">
                                @csrf
                                <button class="btn-accent px-3.5 py-2"><x-icon name="sparkles" class="size-4" /> Promosikan</button>
                            </form>
                        @endif
                        @can('markSold', $product)
                            <form method="POST" action="{{ route('products.mark-sold', $product) }}" onsubmit="return confirm('Tandai barang ini sudah terjual? Barang akan hilang dari katalog.')">
                                @csrf @method('PATCH')
                                <button class="btn-ghost px-3.5 py-2"><x-icon name="check" class="size-4" /> Terjual</button>
                            </form>
                        @endcan
                        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Hapus barang ini? Tindakan ini tidak bisa dibatalkan.')">
                            @csrf @method('DELETE')
                            <button class="inline-flex size-10 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Hapus {{ $product->name }}"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-app-layout>
