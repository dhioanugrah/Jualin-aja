@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-14 text-center']) }}>
    <div class="relative mb-5">
        <div class="absolute -right-1 -top-1 size-5 rounded-full bg-accent-400"></div>
        <div class="flex size-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
            <x-icon :name="$icon" class="size-8" />
        </div>
    </div>
    <h3 class="text-lg font-bold">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
