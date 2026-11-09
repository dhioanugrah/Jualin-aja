@props(['ads'])

{{-- Banner iklan bergeser (F15). Selalu berlabel "Iklan"; klik dicatat lewat route ads.click. --}}
@if ($ads->isNotEmpty())
    <section x-data="{ active: 0, total: {{ $ads->count() }}, timer: null,
                       start() { if (this.total > 1 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.timer = setInterval(() => this.active = (this.active + 1) % this.total, 5000) },
                       stop() { clearInterval(this.timer) } }"
             x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()"
             {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl bg-slate-100 shadow-card']) }}
             aria-roledescription="carousel" aria-label="Iklan">
        <div class="relative aspect-[16/5]">
            @foreach ($ads as $ad)
                <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif x-transition.opacity.duration.300ms class="absolute inset-0" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}">
                    @if ($ad->url)
                        <a href="{{ route('ads.click', $ad) }}" target="_blank" rel="noopener sponsored" class="block size-full">
                    @endif
                    <img src="{{ $ad->image_url }}" alt="{{ $ad->title }} – {{ $ad->advertiser }}" class="size-full object-cover">
                    <span class="absolute bottom-3 left-3 max-w-[70%] truncate rounded-full bg-ink/70 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $ad->title }}</span>
                    @if ($ad->url)
                        </a>
                    @endif
                </div>
            @endforeach
            <span class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 shadow-sm">Iklan</span>
        </div>

        @if ($ads->count() > 1)
            <div class="absolute bottom-3 right-3 flex gap-1.5">
                @foreach ($ads as $ad)
                    <button type="button" @click="active = {{ $loop->index }}" :class="active === {{ $loop->index }} ? 'w-5 bg-white' : 'w-2 bg-white/60'" class="h-2 rounded-full transition-all" aria-label="Iklan {{ $loop->iteration }}"></button>
                @endforeach
            </div>
        @endif
    </section>
@endif
