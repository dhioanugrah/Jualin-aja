<x-app-layout>
    <x-slot:title>Pesan</x-slot:title>

    @php($incoming = $tab === 'masuk')

    <x-page-header title="Pesan" subtitle="Penawaran dan booking COD. Diskusi tetap boleh lewat WhatsApp, kesepakatannya dicatat di sini." />

    <div class="mb-6 inline-flex rounded-full border border-slate-200 bg-white p-1 text-sm font-bold" role="tablist">
        @foreach (['masuk' => ['Masuk', 'Untuk barang saya'], 'keluar' => ['Keluar', 'Yang saya ajukan']] as $key => [$label, $hint])
            <a href="{{ route('inbox', ['tab' => $key]) }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" title="{{ $hint }}"
               @class(['rounded-full px-5 py-2 transition', 'bg-ink text-white' => $tab === $key, 'text-slate-500 hover:text-ink' => $tab !== $key])>{{ $label }}</a>
        @endforeach
    </div>

    @error('offer')
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            <section>
                <h2 class="mb-3 text-lg font-extrabold">Booking aktif <span class="text-sm font-semibold text-slate-400">{{ $activeBookings->count() }}</span></h2>
                @if ($activeBookings->isEmpty())
                    <x-empty-state icon="calendar" :title="$incoming ? 'Belum ada booking masuk' : 'Belum ada booking'" :description="$incoming ? 'Booking dari pembeli untuk barangmu akan muncul di sini.' : 'Temukan barang lalu tekan Hubungi Penjual → Ajukan Booking.'" class="py-10">
                        @unless ($incoming)
                            <x-slot:action><a href="{{ route('catalog') }}" class="btn-primary">Jelajahi katalog</a></x-slot:action>
                        @endunless
                    </x-empty-state>
                @else
                    <div class="card divide-y divide-slate-100 overflow-hidden">
                        @foreach ($activeBookings as $booking)
                            <x-booking-row :booking="$booking" :incoming="$incoming" />
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($pastBookings->isNotEmpty())
                <section>
                    <h2 class="mb-3 text-lg font-extrabold">Riwayat booking</h2>
                    <div class="card divide-y divide-slate-100 overflow-hidden opacity-90">
                        @foreach ($pastBookings as $booking)
                            <x-booking-row :booking="$booking" :incoming="$incoming" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <section>
            <h2 class="mb-3 text-lg font-extrabold">Penawaran <span class="text-sm font-semibold text-slate-400">{{ $offers->count() }}</span></h2>
            @if ($offers->isEmpty())
                <p class="card p-5 text-sm text-slate-500">{{ $incoming ? 'Belum ada yang menawar barangmu.' : 'Kamu belum menawar barang apa pun.' }}</p>
            @else
                <ul class="card divide-y divide-slate-100 overflow-hidden">
                    @foreach ($offers as $offer)
                        @php($isBookableAccepted = $offer->status === \App\Enums\OfferStatus::Accepted && $offer->expires_at?->isFuture() && ! $offer->booking)
                        <li class="p-4">
                            <a href="{{ route('products.show', $offer->product) }}" class="flex items-center gap-3">
                                <div class="size-12 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                    @if ($offer->product->cover_url)<img src="{{ $offer->product->cover_url }}" alt="" class="size-full object-cover">@endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-ink">{{ $offer->product->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $incoming ? $offer->buyer->name : $offer->product->seller->name }} ·
                                        <span class="tabular font-bold text-brand-600">{{ $offer->formatted_price }}</span>
                                        <span class="tabular text-slate-400 line-through">{{ $offer->product->formatted_price }}</span>
                                    </p>
                                </div>
                                <x-status-badge :status="$offer->status" />
                            </a>
                            @if ($offer->message)
                                <p class="mt-2 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">“{{ $offer->message }}”</p>
                            @endif

                            @if ($incoming && $offer->status === \App\Enums\OfferStatus::Pending)
                                <div class="mt-3 flex gap-2">
                                    <form method="POST" action="{{ route('offers.accept', $offer) }}" class="flex-1">@csrf @method('PATCH')<button class="btn-primary w-full py-2">Terima</button></form>
                                    <form method="POST" action="{{ route('offers.reject', $offer) }}" class="flex-1">@csrf @method('PATCH')<button class="btn-ghost w-full py-2">Tolak</button></form>
                                </div>
                            @elseif ($isBookableAccepted)
                                <p class="mt-2 text-xs text-slate-500">Booking harus diajukan sebelum {{ $offer->expires_at->translatedFormat('j M H:i') }}.</p>
                                @unless ($incoming)
                                    <a href="{{ route('bookings.create', ['product' => $offer->product, 'penawaran' => $offer->id]) }}" class="btn-accent mt-2 w-full py-2">Ajukan booking</a>
                                @endunless
                            @elseif ($offer->booking)
                                <a href="{{ route('bookings.show', $offer->booking) }}" class="mt-2 inline-flex text-xs font-semibold text-brand-600 hover:underline">Lihat booking {{ $offer->booking->code }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-app-layout>
