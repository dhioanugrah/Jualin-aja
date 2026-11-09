<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="min-h-dvh">
    <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
        {{-- Panel merek (desktop) --}}
        <aside class="relative hidden overflow-hidden bg-brand-600 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-24 -top-24 size-96 rounded-full bg-accent-400"></div>
            <div class="absolute -bottom-32 -left-20 size-96 rounded-full bg-brand-500/60"></div>

            <a href="{{ route('home') }}" class="relative"><x-logo size="lg" inverse /></a>

            <div class="relative max-w-md">
                <h2 class="text-4xl font-extrabold leading-tight text-white">Barang kakak tingkat,<br>harga teman.</h2>
                <ul class="mt-8 space-y-4 text-brand-50">
                    <li class="flex gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/15"><x-icon name="badge-check" class="size-4" /></span> Hanya mahasiswa ITK terverifikasi yang bisa berjualan.</li>
                    <li class="flex gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/15"><x-icon name="shield-check" class="size-4" /></span> Kesepakatan dicatat di web, bukan cuma janji di chat.</li>
                    <li class="flex gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/15"><x-icon name="map-pin" class="size-4" /></span> COD di titik aman dalam dan sekitar kampus.</li>
                </ul>
            </div>

            <p class="relative text-sm text-brand-100">© {{ now()->year }} Jualin Aja ITK</p>
        </aside>

        <main class="flex flex-col px-5 py-8 sm:px-10">
            <a href="{{ route('home') }}" class="lg:hidden"><x-logo /></a>
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    @include('partials.toast')
</body>
</html>
