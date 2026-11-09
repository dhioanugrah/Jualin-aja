@php($toastMessage = session('toast'))

@if ($toastMessage)
    <div x-data="toast(@js($toastMessage))" x-cloak x-show="show"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-2 opacity-0"
         class="fixed inset-x-4 bottom-24 z-50 mx-auto flex max-w-md items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-sm font-medium text-white shadow-float md:bottom-6"
         role="status">
        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent-400 text-ink"><x-icon name="check" class="size-3.5" stroke="3" /></span>
        <span class="flex-1" x-text="message"></span>
        <button type="button" @click="show = false" class="text-white/60 hover:text-white" aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
    </div>
@endif
