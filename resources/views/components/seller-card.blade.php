@props(['seller'])

{{-- Kartu lapak untuk tab Lapak dan beranda. Butuh withCount active_products_count dan withAvg rating. --}}
<a href="{{ route('sellers.show', $seller) }}" {{ $attributes->merge(['class' => 'card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-float']) }}>
    <x-avatar :user="$seller" size="size-12" />
    <div class="min-w-0 flex-1">
        <p class="flex items-center gap-1 truncate font-bold text-ink">{{ $seller->name }} <x-icon name="badge-check" class="size-4 text-brand-600" /></p>
        <p class="truncate text-xs text-slate-500">{{ $seller->prodi ?? 'Mahasiswa ITK' }}{{ $seller->angkatan ? ' · '.$seller->angkatan : '' }}</p>
        <p class="mt-1 flex items-center gap-2 text-xs text-slate-600">
            @if ($seller->seller_reviews_count > 0)
                <span class="inline-flex items-center gap-0.5 font-semibold"><x-icon name="star" class="size-3.5 text-accent-400" fill="currentColor" /> {{ number_format($seller->seller_reviews_avg_rating, 1, ',', '') }}</span>
                <span class="text-slate-300">·</span>
            @endif
            <span>{{ $seller->active_products_count }} barang</span>
        </p>
    </div>
    <x-icon name="chevron-right" class="size-4 text-slate-300" />
</a>
