@props(['chapter', 'label' => 'Bantuan'])

{{-- Ikon "?" yang menautkan ke bab Buku Panduan terkait (F14). --}}
<a href="{{ route('guide.show', $chapter) }}" target="_blank" {{ $attributes->merge(['class' => 'inline-flex size-7 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-brand-50 hover:text-brand-600']) }} aria-label="{{ $label }}: buka panduan" title="{{ $label }}">
    <x-icon name="help" class="size-[18px]" />
</a>
