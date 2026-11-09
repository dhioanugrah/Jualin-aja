<x-app-layout>
    <x-slot:title>{{ $chapter['title'] }} · Panduan</x-slot:title>

    <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
        <aside class="hidden lg:block">
            <nav class="sticky top-24 space-y-1" aria-label="Bab panduan">
                <a href="{{ route('guide.index') }}" class="mb-3 flex items-center gap-2 text-sm font-semibold text-brand-600 hover:underline"><x-icon name="arrow-left" class="size-4" /> Semua bab</a>
                @foreach ($chapters as $key => $item)
                    <a href="{{ route('guide.show', $key) }}" @class(['flex items-center gap-2 rounded-xl px-3 py-2 text-sm', 'bg-brand-50 font-bold text-brand-700' => $key === $slug, 'text-slate-600 hover:bg-slate-100' => $key !== $slug])>
                        <x-icon :name="$item['icon']" class="size-4" /> {{ $item['title'] }}
                    </a>
                @endforeach
                <a href="{{ route('guide.pdf') }}" class="mt-4 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-50"><x-icon name="download" class="size-4" /> Unduh versi PDF</a>
            </nav>
        </aside>

        <article class="min-w-0">
            <a href="{{ route('guide.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:underline lg:hidden"><x-icon name="arrow-left" class="size-4" /> Buku Panduan</a>
            <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Bab {{ $chapters->keys()->search($slug) + 1 }}</p>
            <h1 class="mt-1 flex items-center gap-2 text-3xl font-extrabold"><x-icon :name="$chapter['icon']" class="size-7 text-brand-600" /> {{ $chapter['title'] }}</h1>
            <p class="mt-2 text-slate-500">{{ $chapter['summary'] }}</p>

            <div class="guide-prose mt-8">
                @include("guide.chapters.{$slug}")
            </div>

            <nav class="mt-10 grid gap-3 border-t border-slate-200 pt-6 sm:grid-cols-2" aria-label="Bab sebelumnya dan berikutnya">
                @if ($previous)
                    <a href="{{ route('guide.show', $previous) }}" class="card p-4 hover:border-brand-200">
                        <span class="text-xs text-slate-500">Sebelumnya</span>
                        <span class="flex items-center gap-1 font-bold text-ink"><x-icon name="arrow-left" class="size-4" /> {{ $chapters[$previous]['title'] }}</span>
                    </a>
                @else
                    <span></span>
                @endif
                @if ($next)
                    <a href="{{ route('guide.show', $next) }}" class="card p-4 text-right hover:border-brand-200">
                        <span class="text-xs text-slate-500">Berikutnya</span>
                        <span class="flex items-center justify-end gap-1 font-bold text-ink">{{ $chapters[$next]['title'] }} <x-icon name="arrow-right" class="size-4" /></span>
                    </a>
                @endif
            </nav>
        </article>
    </div>
</x-app-layout>
