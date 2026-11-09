@props(['product', 'size' => 'size-9'])

{{-- Tombol hati (F12). Tamu diarahkan ke halaman masuk. --}}
@auth
    @php($favorited = auth()->user()->hasFavorited($product))
    <form method="POST" action="{{ route('favorites.toggle', $product) }}" {{ $attributes }}>
        @csrf
        <button type="submit" @class([$size, 'flex items-center justify-center rounded-full bg-white/95 shadow-card backdrop-blur transition hover:scale-105 active:scale-95', 'text-red-500' => $favorited, 'text-slate-500 hover:text-red-500' => ! $favorited])
                aria-pressed="{{ $favorited ? 'true' : 'false' }}" aria-label="{{ $favorited ? 'Hapus dari Favorit' : 'Simpan ke Favorit' }}">
            <x-icon name="heart" class="size-[18px]" :fill="$favorited ? 'currentColor' : 'none'" />
        </button>
    </form>
@else
    <a href="{{ route('login') }}" {{ $attributes->merge(['class' => $size.' flex items-center justify-center rounded-full bg-white/95 text-slate-500 shadow-card backdrop-blur hover:text-red-500']) }} aria-label="Masuk untuk menyimpan ke Favorit">
        <x-icon name="heart" class="size-[18px]" />
    </a>
@endauth
