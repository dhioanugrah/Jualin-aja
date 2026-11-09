@props(['name'])

@error($name)
    <p {{ $attributes->merge(['class' => 'mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600']) }}>
        <x-icon name="alert" class="size-3.5" /> {{ $message }}
    </p>
@enderror
