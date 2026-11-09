@props(['product', 'showStatus' => false, 'showFavorite' => true])

<div {{ $attributes->merge(['class' => 'group card relative flex flex-col overflow-hidden transition duration-200 hover:-translate-y-0.5 hover:shadow-float']) }}>
    <a href="{{ route('products.show', $product) }}" class="flex flex-1 flex-col">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if ($product->cover_url)
                <img src="{{ $product->cover_url }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-cover transition duration-300 group-hover:scale-105">
            @else
                <div class="flex size-full items-center justify-center text-slate-300"><x-icon name="image" class="size-10" /></div>
            @endif

            <div class="absolute left-2 top-2 flex flex-wrap gap-1 pr-12">
                @if ($product->getAttribute('is_promoted'))
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-accent-400 px-2 py-0.5 text-[11px] font-bold text-ink shadow-sm"><x-icon name="sparkles" class="size-3" stroke="2.5" /> Unggulan</span>
                @endif
                @if ($product->is_donation)
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Donasi</span>
                @endif
                @if ($showStatus)
                    <x-status-badge :status="$product->status" class="bg-white/95" />
                @endif
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-1 p-3 sm:p-4">
            <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-ink">{{ $product->name }}</h3>
            <p class="tabular text-base font-extrabold text-brand-600">{{ $product->formatted_price }}</p>
            <div class="mt-auto flex items-center gap-1 pt-1 text-xs text-slate-500">
                <x-icon name="map-pin" class="size-3.5" />
                <span class="truncate">{{ $product->cod_areas_label }}</span>
                <span class="text-slate-300">·</span>
                <span class="shrink-0">{{ $product->condition->label() }}</span>
            </div>
        </div>
    </a>

    @if ($showFavorite && auth()->id() !== $product->user_id)
        <x-favorite-button :product="$product" class="absolute right-2 top-2" />
    @endif
</div>
