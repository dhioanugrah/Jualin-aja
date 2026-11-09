<x-app-layout>
    <x-slot:title>Favorit</x-slot:title>

    <x-page-header title="Favorit" subtitle="Barang yang kamu simpan. Barang yang sudah terjual otomatis hilang dari daftar ini." />

    @if ($products->isEmpty())
        <x-empty-state icon="heart" title="Belum ada favorit" description="Tekan ikon hati di kartu barang untuk menyimpannya di sini.">
            <x-slot:action><a href="{{ route('catalog') }}" class="btn-primary">Jelajahi katalog</a></x-slot:action>
        </x-empty-state>
    @else
        <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
</x-app-layout>
