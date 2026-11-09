<x-app-layout>
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-500 to-[#3b82d8] px-6 py-10 text-white sm:px-10 sm:py-14">
        <div class="absolute -right-24 -top-24 size-48 rounded-full bg-accent-400 sm:-right-10 sm:-top-16 sm:size-80"></div>
        <div class="absolute -bottom-24 right-40 size-56 rounded-full bg-white/10"></div>
        <div class="relative max-w-xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur"><x-icon name="badge-check" class="size-3.5" /> Khusus mahasiswa ITK</span>
            <h1 class="mt-4 text-3xl font-extrabold leading-tight text-white sm:text-5xl">Barang kakak tingkat,<br>harga teman.</h1>
            <p class="mt-3 text-brand-50 sm:text-lg">Buku, alat praktikum, sampai perabot kos. COD aman di dalam dan sekitar kampus.</p>

            <form action="{{ route('catalog') }}" method="GET" class="relative mt-6 max-w-md" role="search">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" placeholder="Cari buku, kipas, jas lab…" aria-label="Cari barang"
                       class="h-12 w-full rounded-full border-0 bg-white pl-12 pr-28 text-sm text-ink shadow-float placeholder:text-slate-400 focus:ring-4 focus:ring-accent-300">
                <button class="btn-accent absolute right-1.5 top-1.5 h-9 px-4 py-0">Cari</button>
            </form>

            @if ($popularSearches->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 text-brand-100"><x-icon name="trending" class="size-3.5" /> Populer:</span>
                    @foreach ($popularSearches as $term)
                        <a href="{{ route('catalog', ['q' => $term]) }}" class="rounded-full bg-white/15 px-2.5 py-1 font-semibold text-white backdrop-blur hover:bg-white/25">{{ $term }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-ad-carousel :ads="$ads" class="mt-6" />

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between">
            <h2 class="text-xl font-extrabold sm:text-2xl">Jelajah per jenis</h2>
            <a href="{{ route('catalog') }}" class="text-sm font-semibold text-brand-600 hover:underline">Semua barang</a>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 sm:gap-3 lg:grid-cols-7">
            @foreach ($categories as $category)
                <a href="{{ route('catalog', ['kategori' => $category->slug]) }}" class="card flex flex-col items-center gap-2 px-2 py-4 text-center transition hover:-translate-y-0.5 hover:border-brand-200">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"><x-icon :name="$category->icon" /></span>
                    <span class="text-xs font-bold leading-tight text-ink sm:text-sm">{{ $category->name }}</span>
                    <span class="tabular text-[11px] text-slate-400">{{ $category->products_count }} barang</span>
                </a>
            @endforeach
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="mt-10 rounded-3xl bg-accent-50 p-4 ring-1 ring-accent-300/50 sm:p-6">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 class="flex items-center gap-2 text-xl font-extrabold sm:text-2xl"><x-icon name="sparkles" class="text-accent-500" /> Barang Unggulan</h2>
                    <p class="text-sm text-slate-600">Dipromosikan penjual, tampil bergiliran</p>
                </div>
                <a href="{{ route('guide.show', 'promosi') }}" class="text-sm font-semibold text-brand-600 hover:underline">Cara promosi</a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-10">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold sm:text-2xl">Barang terbaru</h2>
                <p class="text-sm text-slate-500">Baru lolos moderasi admin</p>
            </div>
            <a href="{{ route('catalog') }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua</a>
        </div>

        @if ($products->isEmpty())
            <x-empty-state icon="package" title="Belum ada barang" description="Jadilah yang pertama berjualan di Jualin Aja ITK." />
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" class="animate-fade-up" style="animation-delay: {{ min($loop->index, 8) * 30 }}ms" />
                @endforeach
            </div>
        @endif
    </section>

    @if ($topSellers->isNotEmpty())
        <section class="mt-10">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <h2 class="text-xl font-extrabold sm:text-2xl">Jelajah per penjual</h2>
                    <p class="text-sm text-slate-500">Lapak dengan rating dan barang terbanyak</p>
                </div>
                <a href="{{ route('catalog', ['tab' => 'lapak']) }}" class="text-sm font-semibold text-brand-600 hover:underline">Semua lapak</a>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($topSellers as $seller)
                    <x-seller-card :seller="$seller" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-extrabold sm:text-2xl">Kata mereka yang sudah COD</h2>
            <div class="grid gap-3 md:grid-cols-3">
                @foreach ($testimonials as $review)
                    <figure class="card flex flex-col p-5">
                        <span class="flex text-accent-400" aria-label="{{ $review->rating }} dari 5 bintang">
                            @for ($i = 1; $i <= 5; $i++)
                                <x-icon name="star" class="size-4" :fill="$i <= $review->rating ? 'currentColor' : 'none'" />
                            @endfor
                        </span>
                        <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-slate-700">“{{ $review->comment }}”</blockquote>
                        <figcaption class="mt-4 flex items-center gap-2 text-xs text-slate-500">
                            <x-avatar :user="$review->reviewer" size="size-7" />
                            <span><span class="font-bold text-ink">{{ $review->reviewer->name }}</span> tentang lapak <a href="{{ route('sellers.show', $review->reviewee) }}" class="font-semibold text-brand-600 hover:underline">{{ $review->reviewee->name }}</a></span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @guest
        <section class="mt-12 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['icon' => 'badge-check', 'title' => 'Penjual terverifikasi', 'text' => 'Hanya akun email kampus ITK yang bisa berjualan.'],
                ['icon' => 'shield-check', 'title' => 'Dimoderasi admin', 'text' => 'Setiap barang dicek dulu sebelum tampil di katalog.'],
                ['icon' => 'key', 'title' => 'Kode serah terima', 'text' => 'Booking COD dikunci di web dan selesai dengan kode 4 digit.'],
            ] as $point)
                <div class="card flex gap-4 p-5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon :name="$point['icon']" /></span>
                    <div>
                        <h3 class="font-bold">{{ $point['title'] }}</h3>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $point['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>
    @endguest
</x-app-layout>
