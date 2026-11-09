<x-app-layout>
    <x-slot:title>Jual Barang</x-slot:title>

    <x-page-header title="Jual Barang" subtitle="Barang akan dicek admin dulu sebelum tampil di katalog." :back="route('seller.dashboard')" />

    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('products._form')

        <div class="sticky bottom-20 z-30 mt-6 flex justify-end gap-3 rounded-2xl border border-slate-200 bg-white/90 p-3 shadow-float backdrop-blur md:bottom-4">
            <a href="{{ route('my-products.index') }}" class="btn-ghost">Batal</a>
            <button class="btn-accent flex-1 sm:flex-none">Kirim untuk moderasi</button>
        </div>
    </form>
</x-app-layout>
