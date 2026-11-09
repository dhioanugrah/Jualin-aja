@props(['size' => 'md', 'inverse' => false])

@php
    $markSize = ['sm' => 'size-7', 'md' => 'size-9', 'lg' => 'size-12'][$size];
    $textSize = ['sm' => 'text-base', 'md' => 'text-lg', 'lg' => 'text-2xl'][$size];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <x-logo-mark :class="$markSize" />
    <span @class([$textSize, 'font-extrabold tracking-tight leading-none', 'text-white' => $inverse, 'text-brand-600' => ! $inverse])>
        Jualin<span class="text-accent-400">Aja</span>
        <span @class(['font-bold', 'text-white/80' => $inverse, 'text-ink' => ! $inverse])>ITK</span>
    </span>
</span>
