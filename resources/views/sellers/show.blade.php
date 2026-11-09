<x-app-layout>
    <x-slot:title>Lapak {{ $seller->name }}</x-slot:title>

    <section class="card relative overflow-hidden p-6 sm:p-8">
        <div class="absolute -right-16 -top-16 size-40 rounded-full bg-brand-50"></div>
        <div class="absolute -right-2 -top-2 size-10 rounded-full bg-accent-400"></div>
        <div class="relative flex flex-wrap items-center gap-4">
            <x-avatar :user="$seller" size="size-20" class="text-lg" />
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Lapak</p>
                <h1 class="flex items-center gap-1.5 text-2xl font-extrabold sm:text-3xl">{{ $seller->name }} <x-icon name="badge-check" class="size-6 text-brand-600" /></h1>
                <p class="text-sm text-slate-500">{{ $seller->prodi ?? 'Mahasiswa ITK' }}{{ $seller->angkatan ? ' · Angkatan '.$seller->angkatan : '' }} · Bergabung {{ $seller->created_at->translatedFormat('F Y') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-reliability-badge :reliability="$reliability" new-label="Penjual baru" />
                    @if (\App\Models\User::isTopSeller($rating))
                        <span class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2.5 py-0.5 text-xs font-bold text-accent-500"><x-icon name="star" class="size-3.5" fill="currentColor" /> Penjual Terbaik</span>
                    @endif
                    @if ($buyerRating['count'] > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600" title="Rating dari penjual saat ia membeli"><x-icon name="bag" class="size-3.5" /> Sebagai pembeli {{ number_format($buyerRating['average'], 1, ',', '') }} ({{ $buyerRating['count'] }})</span>
                    @endif
                    @foreach ($topTags as $tag)
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <dl class="relative mt-6 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl bg-slate-50 p-3">
                <dt class="text-xs text-slate-500">Rating</dt>
                <dd class="tabular mt-0.5 flex items-center justify-center gap-1 text-xl font-extrabold text-ink">
                    @if ($rating['count'] > 0)
                        <x-icon name="star" class="size-4 text-accent-400" fill="currentColor" /> {{ number_format($rating['average'], 1, ',', '') }}
                        <span class="text-xs font-medium text-slate-400">({{ $rating['count'] }})</span>
                    @else
                        <span class="text-sm font-semibold text-slate-400">Belum ada</span>
                    @endif
                </dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3">
                <dt class="text-xs text-slate-500">Barang tayang</dt>
                <dd class="tabular mt-0.5 text-xl font-extrabold text-ink">{{ $products->total() }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3">
                <dt class="text-xs text-slate-500">Terjual lewat web</dt>
                <dd class="tabular mt-0.5 text-xl font-extrabold text-ink">{{ $soldCount }}</dd>
            </div>
        </dl>
    </section>

    <section class="mt-8">
        <h2 class="mb-4 text-xl font-extrabold">Barang di lapak ini</h2>
        @if ($products->isEmpty())
            <x-empty-state icon="package" title="Lapak ini sedang kosong" description="Belum ada barang yang tayang. Coba cek lagi nanti." />
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>

    <section class="mt-10">
        <h2 class="mb-4 text-xl font-extrabold">Ulasan pembeli</h2>
        @if ($reviews->isEmpty())
            <p class="card p-5 text-sm text-slate-500">Belum ada ulasan. Ulasan muncul setelah pembeli menyelesaikan COD dengan kode serah terima.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($reviews as $review)
                    <article class="card p-4">
                        <div class="flex items-center gap-2">
                            <x-avatar :user="$review->reviewer" size="size-8" />
                            <p class="flex-1 truncate text-sm font-bold text-ink">{{ $review->reviewer->name }}</p>
                            <span class="flex text-accent-400" aria-label="{{ $review->rating }} dari 5 bintang">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-icon name="star" class="size-3.5" :fill="$i <= $review->rating ? 'currentColor' : 'none'" />
                                @endfor
                            </span>
                        </div>
                        @if ($review->comment)
                            <p class="mt-2 text-sm text-slate-600">{{ $review->comment }}</p>
                        @endif
                        @if ($review->tags)
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($review->tags as $tag)
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                        <p class="mt-2 text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @auth
        @if (auth()->id() !== $seller->id && ! auth()->user()->isAdmin() && auth()->user()->hasVerifiedEmail())
            @include('partials.report-dialog', ['type' => 'lapak', 'id' => $seller->id, 'label' => 'Lapak '.$seller->name, 'wrapperClass' => 'mt-8 flex justify-end'])
        @endif
    @endauth
</x-app-layout>
