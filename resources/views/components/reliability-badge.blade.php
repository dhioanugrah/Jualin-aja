@props(['reliability', 'newLabel' => 'Baru'])

{{-- Skor keandalan (Proposal 3.3 lapis 7), contoh: "Andalan 92% · 12 transaksi". --}}
@if ($reliability === null)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600']) }}>
        <x-icon name="sparkles" class="size-3.5" /> {{ $newLabel }}
    </span>
@else
    @php($tone = $reliability['percent'] >= 80 ? 'bg-emerald-50 text-emerald-700' : ($reliability['percent'] >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'))
    <span {{ $attributes->merge(['class' => 'tabular inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold '.$tone]) }}>
        <x-icon name="shield-check" class="size-3.5" /> Andalan {{ $reliability['percent'] }}% · {{ $reliability['total'] }} transaksi
    </span>
@endif
