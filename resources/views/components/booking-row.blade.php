@props(['booking', 'incoming'])

@php($other = $incoming ? $booking->buyer : $booking->seller)
<a href="{{ route('bookings.show', $booking) }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 p-4 transition hover:bg-slate-50']) }}>
    <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-slate-100">
        @if ($booking->product->cover_url)<img src="{{ $booking->product->cover_url }}" alt="" class="size-full object-cover">@endif
    </div>
    <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-2">
            <span class="truncate font-bold text-ink">{{ $booking->product->name }}</span>
            <x-status-badge :status="$booking->status" />
        </p>
        <p class="truncate text-xs text-slate-500">
            {{ $incoming ? 'Dari' : 'Ke' }} {{ $other->name }} · {{ $booking->code }} · <span class="tabular font-semibold text-brand-600">{{ $booking->formatted_price }}</span>
        </p>
        <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-slate-600">
            <x-icon name="calendar" class="size-3.5" /> {{ $booking->cod_at->translatedFormat('D, j M H:i') }}
            <span class="text-slate-300">·</span> {{ $booking->codLocation->name }}
        </p>
    </div>
    <x-icon name="chevron-right" class="size-4 text-slate-300" />
</a>
