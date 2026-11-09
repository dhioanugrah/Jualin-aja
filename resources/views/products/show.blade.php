<x-app-layout>
    <x-slot:title>{{ $product->name }}</x-slot:title>

    @php($isOwner = auth()->id() === $product->user_id)

    @push('meta')
        <meta property="og:type" content="product">
        <meta property="og:title" content="{{ $product->name }} · {{ $product->formatted_price }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($product->description, 150) }} COD {{ $product->cod_areas_label }}.">
        <meta property="og:url" content="{{ route('products.show', $product) }}">
        @if ($product->images->isNotEmpty())
            <meta property="og:image" content="{{ $product->images->first()->url }}">
        @endif
    @endpush

    @if ($product->status !== \App\Enums\ProductStatus::Approved)
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="eye" class="size-4" />
            <span>Pratinjau. Status barang ini <x-status-badge :status="$product->status" />, jadi belum tampil di katalog publik.</span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr] lg:gap-10">
        {{-- Galeri foto --}}
        <div x-data="{ active: 0, total: {{ $product->images->count() }} }" class="space-y-3">
            <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-slate-100">
                @unless ($isOwner)
                    <x-favorite-button :product="$product" size="size-11" class="absolute right-3 top-3 z-10" />
                @endunless
                @foreach ($product->images as $image)
                    <img x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif src="{{ $image->url }}" alt="{{ $product->name }} – foto {{ $loop->iteration }}" class="size-full object-cover" x-transition.opacity>
                @endforeach

                @if ($product->images->count() > 1)
                    <button type="button" @click="active = (active - 1 + total) % total" class="absolute left-3 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-card backdrop-blur hover:bg-white" aria-label="Foto sebelumnya"><x-icon name="chevron-left" /></button>
                    <button type="button" @click="active = (active + 1) % total" class="absolute right-3 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-card backdrop-blur hover:bg-white" aria-label="Foto berikutnya"><x-icon name="chevron-right" /></button>
                    <span class="absolute bottom-3 right-3 rounded-full bg-ink/70 px-2.5 py-1 text-xs font-semibold text-white" x-text="`${active + 1} / ${total}`"></span>
                @endif
            </div>

            @if ($product->images->count() > 1)
                <div class="flex gap-2 overflow-x-auto">
                    @foreach ($product->images as $image)
                        <button type="button" @click="active = {{ $loop->index }}" :class="active === {{ $loop->index }} ? 'ring-2 ring-brand-600 ring-offset-2' : 'opacity-70 hover:opacity-100'" class="size-16 shrink-0 overflow-hidden rounded-xl sm:size-20">
                            <img src="{{ $image->url }}" alt="" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info barang --}}
        <div class="space-y-5">
            <div>
                <a href="{{ route('catalog', ['kategori' => $product->category->slug]) }}" class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-brand-600 hover:underline">
                    <x-icon :name="$product->category->icon" class="size-3.5" /> {{ $product->category->name }}
                </a>
                <h1 class="mt-2 text-2xl font-extrabold leading-tight sm:text-3xl">{{ $product->name }}</h1>
                @if ($isPromoted)
                    <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-accent-400 px-2.5 py-0.5 text-xs font-bold text-ink"><x-icon name="sparkles" class="size-3.5" /> Unggulan</span>
                @endif
                <p class="tabular mt-3 text-3xl font-extrabold text-brand-600">{{ $product->formatted_price }}</p>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200/70"><dt class="text-xs text-slate-500">Kondisi</dt><dd class="font-bold text-ink">{{ $product->condition->label() }}</dd></div>
                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200/70"><dt class="text-xs text-slate-500">Penyerahan</dt><dd class="font-bold text-ink">{{ $product->handover_method->label() }}</dd></div>
                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200/70"><dt class="text-xs text-slate-500">Area COD</dt><dd class="font-bold text-ink">{{ $product->cod_areas_label }}</dd></div>
                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200/70"><dt class="text-xs text-slate-500">Lokasi umum</dt><dd class="font-bold text-ink">{{ $product->location ?: '–' }}</dd></div>
            </dl>

            <div>
                <h2 class="mb-1.5 text-sm font-bold">Deskripsi</h2>
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $product->description }}</p>
            </div>

            {{-- Kartu penjual --}}
            <a href="{{ route('sellers.show', $product->seller) }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-200">
                <x-avatar :user="$product->seller" size="size-12" />
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-1 truncate font-bold text-ink">{{ $product->seller->name }} <x-icon name="badge-check" class="size-4 text-brand-600" /></p>
                    <p class="text-xs text-slate-500">{{ $product->seller->prodi ?? 'Mahasiswa ITK' }}{{ $product->seller->angkatan ? ' · '.$product->seller->angkatan : '' }}</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs">
                        @if (\App\Models\User::isTopSeller($sellerRating))
                            <span class="inline-flex items-center gap-0.5 rounded-full bg-accent-50 px-2 py-0.5 font-bold text-accent-500"><x-icon name="star" class="size-3" fill="currentColor" /> Penjual Terbaik</span>
                        @endif
                        @if ($sellerRating['count'] > 0)
                            <span class="inline-flex items-center gap-0.5 font-semibold text-ink"><x-icon name="star" class="size-3.5 text-accent-400" fill="currentColor" /> {{ number_format($sellerRating['average'], 1, ',', '') }} <span class="font-normal text-slate-400">({{ $sellerRating['count'] }})</span></span>
                        @endif
                        <x-reliability-badge :reliability="$sellerReliability" new-label="Penjual baru" />
                    </div>
                </div>
                <span class="hidden text-xs font-semibold text-brand-600 sm:block">Lihat lapak</span>
                <x-icon name="chevron-right" class="size-4 text-slate-300" />
            </a>

            @if ($isOwner && $product->status === \App\Enums\ProductStatus::Approved)
                @php($quota = auth()->user()->promotionQuotaLeft())
                <div class="rounded-2xl border border-accent-300/60 bg-accent-50 p-4 text-sm">
                    <p class="flex items-center gap-1.5 font-bold text-ink"><x-icon name="sparkles" class="size-4 text-accent-500" /> Promosi gratis</p>
                    @if ($isPromoted)
                        <p class="mt-1 text-slate-600">Barang ini sedang tampil di slot Barang Unggulan.</p>
                    @elseif ($quota > 0)
                        <p class="mt-1 text-slate-600">Tampilkan barang ini di Barang Unggulan beranda dan teratas di kategorinya selama {{ config('jualin.promotion_days') }} hari. Kuota: {{ $quota }} per minggu.</p>
                        <form method="POST" action="{{ route('products.promote', $product) }}" class="mt-3">
                            @csrf
                            <button class="btn-accent w-full">Promosikan sekarang</button>
                        </form>
                    @else
                        <p class="mt-1 text-slate-600">Kuota minggu ini sudah terpakai. Kuota berikutnya {{ auth()->user()->nextPromotionAt()?->translatedFormat('j M H:i') }}.</p>
                    @endif
                    @error('promotion')<p class="mt-2 font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
            @endif

            @if ($product->status === \App\Enums\ProductStatus::Approved)
                @include('products._share')
            @endif

            @if ($isOwner)
                <div class="flex flex-wrap gap-2">
                    @can('update', $product)
                        <a href="{{ route('products.edit', $product) }}" class="btn-ghost"><x-icon name="pencil" class="size-4" /> Edit barang</a>
                    @endcan
                    <a href="{{ route('my-products.index') }}" class="btn-ghost">Barang Saya</a>
                    <a href="{{ route('inbox', ['tab' => 'masuk']) }}" class="btn-ghost"><x-icon name="inbox" class="size-4" /> Booking masuk</a>
                </div>
            @elseif ($product->status === \App\Enums\ProductStatus::Sold)
                <div class="rounded-2xl bg-slate-100 p-4 text-center text-sm font-semibold text-slate-600">Barang ini sudah terjual.</div>
            @elseif (! auth()->check())
                <div class="rounded-2xl border border-dashed border-brand-200 bg-brand-50/60 p-4 text-center">
                    <a href="{{ route('login') }}" class="btn-primary w-full py-3"><x-icon name="message-circle" class="size-4" /> Masuk untuk menghubungi penjual</a>
                    <p class="mt-2 text-xs text-slate-500">Hanya mahasiswa ITK terverifikasi yang bisa menghubungi, menawar, dan booking. <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:underline">Daftar</a></p>
                </div>
            @elseif (! auth()->user()->hasVerifiedEmail())
                <div class="rounded-2xl border border-accent-300/60 bg-accent-50 p-4 text-sm text-ink">
                    Verifikasi email kampus dulu supaya bisa menghubungi penjual. <a href="{{ route('verification.notice') }}" class="font-bold text-brand-600 hover:underline">Kirim ulang email verifikasi</a>
                </div>
            @elseif (! auth()->user()->isAdmin())
                <div x-data="{ open: {{ $errors->hasAny(['price', 'message', 'contact']) ? 'true' : 'false' }} }" class="space-y-3">
                    @if ($myBooking)
                        <a href="{{ route('bookings.show', $myBooking) }}" class="flex items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 hover:bg-brand-100/60">
                            <x-icon name="calendar" class="text-brand-600" />
                            <div class="min-w-0 flex-1 text-sm">
                                <p class="font-bold text-ink">Booking {{ $myBooking->code }} · <x-status-badge :status="$myBooking->status" /></p>
                                <p class="text-slate-600">COD {{ $myBooking->cod_at->translatedFormat('l, j M H:i') }}</p>
                            </div>
                            <x-icon name="chevron-right" class="size-4 text-slate-400" />
                        </a>
                    @elseif ($myOffer)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-bold text-ink">Tawaranmu {{ $myOffer->formatted_price }} <x-status-badge :status="$myOffer->status" /></p>
                                <form method="POST" action="{{ route('offers.cancel', $myOffer) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-semibold text-slate-500 hover:text-red-600">Batalkan</button>
                                </form>
                            </div>
                            @if ($myOffer->status === \App\Enums\OfferStatus::Accepted)
                                <p class="mt-1 text-slate-600">Penjual setuju. Ajukan booking sebelum <strong>{{ $myOffer->expires_at->translatedFormat('j M H:i') }}</strong>, atau tawaran kedaluwarsa.</p>
                                <a href="{{ route('bookings.create', ['product' => $product, 'penawaran' => $myOffer->id]) }}" class="btn-accent mt-3 w-full">Booking dengan harga {{ $myOffer->formatted_price }}</a>
                            @else
                                <p class="mt-1 text-slate-500">Menunggu respons penjual.</p>
                            @endif
                        </div>
                    @endif

                    <button type="button" @click="open = true" class="btn-primary w-full py-3"><x-icon name="message-circle" class="size-4" /> Hubungi Penjual</button>
                    <p class="text-center text-xs text-slate-500">Diskusi boleh lewat WhatsApp, tapi kesepakatan hanya sah kalau dikunci di web.</p>
                    @error('contact')<p class="text-center text-sm font-semibold text-red-600">{{ $message }}</p>@enderror

                    {{-- Modal Hubungi Penjual: bottom sheet di HP, dialog di desktop --}}
                    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="hubungi-judul" @keydown.escape.window="open = false">
                        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="open = false"></div>
                        <div x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-4" x-transition:enter-end="translate-y-0 opacity-100"
                             class="relative max-h-[90dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-float sm:max-w-md sm:rounded-3xl sm:p-6">
                            <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-slate-200 sm:hidden"></div>
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h2 id="hubungi-judul" class="text-lg font-extrabold">Hubungi {{ $product->seller->name }}</h2>
                                    <p class="text-sm text-slate-500">{{ $product->name }} · {{ $product->formatted_price }}</p>
                                </div>
                                <button type="button" @click="open = false" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-ink" aria-label="Tutup"><x-icon name="x" /></button>
                            </div>

                            <div class="space-y-3">
                                <form method="POST" action="{{ route('products.contact', $product) }}" target="_blank" @submit="setTimeout(() => open = false, 300)">
                                    @csrf
                                    <button class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 p-4 text-left transition hover:border-emerald-300 hover:bg-emerald-50/50">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="message-circle" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-bold text-ink">Diskusi via WhatsApp</span>
                                            <span class="block text-xs text-slate-500">Tanya kondisi atau nego. Kontak tercatat, maks. {{ config('jualin.contact_daily_limit') }} barang baru per hari.</span>
                                        </span>
                                        <x-icon name="external" class="size-4 text-slate-300" />
                                    </button>
                                </form>

                                @if (! $product->is_donation && ! $myOffer && ! $myBooking)
                                    <details class="group rounded-2xl border border-slate-200 open:border-brand-200 open:bg-brand-50/40" @if ($errors->hasAny(['price', 'message'])) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center gap-3 p-4">
                                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="tag" /></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-bold text-ink">Ajukan Penawaran</span>
                                                <span class="block text-xs text-slate-500">Tawar harga. Jika diterima, booking dalam {{ config('jualin.offer_booking_window_hours') }} jam.</span>
                                            </span>
                                            <x-icon name="chevron-down" class="size-4 text-slate-400 transition group-open:rotate-180" />
                                        </summary>
                                        <form method="POST" action="{{ route('offers.store', $product) }}" class="space-y-3 px-4 pb-4">
                                            @csrf
                                            <div>
                                                <label for="offer-price" class="field-label">Harga tawaran</label>
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">Rp</span>
                                                    <input id="offer-price" name="price" type="number" inputmode="numeric" min="1000" step="500" max="{{ max($product->price - 1, 1000) }}" value="{{ old('price') }}" required class="field tabular pl-11" placeholder="{{ number_format((int) ($product->price * 0.9), 0, ',', '.') }}">
                                                </div>
                                                <x-field-error name="price" />
                                            </div>
                                            <div>
                                                <label for="offer-message" class="field-label">Pesan <span class="font-normal text-slate-400">(opsional)</span></label>
                                                <textarea id="offer-message" name="message" rows="2" maxlength="500" class="field" placeholder="Contoh: bisa COD besok siang di perpustakaan?">{{ old('message') }}</textarea>
                                                <x-field-error name="message" />
                                            </div>
                                            <button class="btn-primary w-full">Kirim penawaran</button>
                                        </form>
                                    </details>
                                @endif

                                @unless ($myBooking)
                                    <a href="{{ route('bookings.create', array_filter(['product' => $product, 'penawaran' => $myOffer?->status === \App\Enums\OfferStatus::Accepted ? $myOffer->id : null])) }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-accent-300 hover:bg-accent-50/60">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-500"><x-icon name="calendar" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-bold text-ink">Ajukan Booking COD</span>
                                            <span class="block text-xs text-slate-500">Kunci waktu dan titik COD. Dapat kode serah terima 4 digit.</span>
                                        </span>
                                        <x-icon name="chevron-right" class="size-4 text-slate-300" />
                                    </a>
                                @endunless
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @auth
        @if (! $isOwner && ! auth()->user()->isAdmin() && auth()->user()->hasVerifiedEmail() && $product->status === \App\Enums\ProductStatus::Approved)
            @include('partials.report-dialog', ['type' => 'barang', 'id' => $product->id, 'label' => $product->name, 'wrapperClass' => 'mt-6 flex justify-end'])
        @endif
    @endauth

    @if ($moreFromSeller->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-4 text-xl font-extrabold">Barang lain dari lapak ini</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-4">
                @foreach ($moreFromSeller as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
