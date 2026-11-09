@props(['title', 'subtitle' => null, 'back' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="flex items-start gap-3">
        @if ($back)
            <a href="{{ $back }}" class="mt-0.5 inline-flex size-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:text-ink" aria-label="Kembali">
                <x-icon name="arrow-left" class="size-4" />
            </a>
        @endif
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
