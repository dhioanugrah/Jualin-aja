<x-app-layout>
    <x-slot:title>Edit {{ $product->name }}</x-slot:title>

    <x-page-header title="Edit barang" :back="route('my-products.index')">
        <x-slot:subtitle>Setelah disimpan, barang kembali menunggu moderasi admin.</x-slot:subtitle>
    </x-page-header>

    @if ($product->moderation_note)
        <div class="mb-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <x-icon name="alert" class="size-5 text-red-500" />
            <div>
                <p class="font-bold">Catatan admin</p>
                <p class="mt-0.5">{{ $product->moderation_note }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('products._form')

        <div class="sticky bottom-20 z-30 mt-6 flex justify-end gap-3 rounded-2xl border border-slate-200 bg-white/90 p-3 shadow-float backdrop-blur md:bottom-4">
            <a href="{{ route('my-products.index') }}" class="btn-ghost">Batal</a>
            <button class="btn-accent flex-1 sm:flex-none">Simpan & kirim ulang</button>
        </div>
    </form>
</x-app-layout>
