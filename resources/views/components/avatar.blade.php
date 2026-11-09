@props(['user', 'size' => 'size-9'])

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" {{ $attributes->merge(['class' => $size.' shrink-0 rounded-full object-cover ring-2 ring-white']) }}>
@else
    <span {{ $attributes->merge(['class' => $size.' inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 ring-2 ring-white']) }}>
        {{ $user->initials }}
    </span>
@endif
