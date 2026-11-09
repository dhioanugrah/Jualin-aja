<x-app-layout>
    <x-slot:title>Booking {{ $booking->code }}</x-slot:title>

    @php
        $user = auth()->user();
        $status = $booking->status;
        $isParty = $booking->involves($user);
        $confirmOpen = $booking->isInConfirmWindow();
        $past = $booking->isPastSchedule();
    @endphp

    <div class="mx-auto max-w-3xl">
        <x-page-header :title="'Booking '.$booking->code" :back="route('inbox', ['tab' => $isBuyer ? 'keluar' : 'masuk'])">
            <x-slot:subtitle>{{ $isBuyer ? 'Kamu sebagai pembeli' : ($isParty ? 'Kamu sebagai penjual' : 'Dilihat sebagai admin') }} · dibuat {{ $booking->created_at->translatedFormat('j M Y H:i') }}</x-slot:subtitle>
            <x-slot:actions><x-status-badge :status="$status" class="px-3 py-1 text-sm" /></x-slot:actions>
        </x-page-header>

        @error('booking')
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-5 md:grid-cols-[1.3fr_1fr]">
            <div class="space-y-5">
                {{-- Barang dan jadwal --}}
                <section class="card overflow-hidden">
                    <a href="{{ route('products.show', $booking->product) }}" class="flex items-center gap-3 border-b border-slate-100 p-4 hover:bg-slate-50">
                        <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            @if ($booking->product->cover_url)<img src="{{ $booking->product->cover_url }}" alt="" class="size-full object-cover">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-ink">{{ $booking->product->name }}</p>
                            <p class="tabular font-extrabold text-brand-600">{{ $booking->formatted_price }} @if ($booking->offer_id)<span class="text-xs font-semibold text-slate-400">· hasil penawaran</span>@endif</p>
                        </div>
                        <x-icon name="chevron-right" class="size-4 text-slate-300" />
                    </a>
                    <dl class="grid gap-4 p-4 text-sm sm:grid-cols-2">
                        <div class="flex gap-3">
                            <x-icon name="calendar" class="mt-0.5 text-brand-600" />
                            <div><dt class="text-xs text-slate-500">Waktu COD</dt><dd class="font-bold text-ink">{{ $booking->cod_at->translatedFormat('l, j F Y') }}<br>Pukul {{ $booking->cod_at->format('H.i') }} WITA</dd></div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="map-pin" class="mt-0.5 text-brand-600" />
                            <div>
                                <dt class="text-xs text-slate-500">{{ $booking->cod_area->label() }}</dt>
                                <dd class="font-bold text-ink">{{ $booking->codLocation->name }}</dd>
                                @if ($booking->codLocation->maps_url)
                                    <a href="{{ $booking->codLocation->maps_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline">Google Maps <x-icon name="external" class="size-3" /></a>
                                @endif
                            </div>
                        </div>
                        @if ($booking->note)
                            <div class="flex gap-3 sm:col-span-2">
                                <x-icon name="message-circle" class="mt-0.5 text-brand-600" />
                                <div><dt class="text-xs text-slate-500">Catatan pembeli</dt><dd class="text-slate-700">{{ $booking->note }}</dd></div>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- Aksi utama sesuai status --}}
                @if ($status === \App\Enums\BookingStatus::Pending)
                    <section class="card p-5">
                        @if ($isBuyer)
                            <h2 class="font-bold">Menunggu respons penjual</h2>
                            <p class="mt-1 text-sm text-slate-600">Penjual harus menerima sebelum <strong>{{ $booking->respond_by->translatedFormat('j M H:i') }}</strong>. Kalau tidak, booking batal otomatis dan tidak dihitung pelanggaran.</p>
                        @elseif ($isParty)
                            <h2 class="font-bold">Terima booking ini?</h2>
                            <p class="mt-1 text-sm text-slate-600">Respons sebelum <strong>{{ $booking->respond_by->translatedFormat('j M H:i') }}</strong>. Satu barang hanya bisa punya satu booking diterima; booking lain tetap antre sebagai cadangan.</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('bookings.accept', $booking) }}">@csrf @method('PATCH')<button class="btn-primary"><x-icon name="check" class="size-4" /> Terima</button></form>
                                <form method="POST" action="{{ route('bookings.reject', $booking) }}">@csrf @method('PATCH')<button class="btn-ghost">Tolak</button></form>
                            </div>
                        @endif
                    </section>
                @endif

                @if ($status === \App\Enums\BookingStatus::Accepted)
                    <section class="card p-5">
                        <h2 class="flex items-center gap-2 font-bold"><x-icon name="clock" class="size-4 text-brand-600" /> Konfirmasi kehadiran</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Dibuka {{ $booking->confirmOpensAt()->translatedFormat('j M H:i') }}. Batas akhir <strong>{{ $booking->confirmDeadline()->translatedFormat('j M H:i') }}</strong>; yang belum konfirmasi saat itu dicatat pelanggaran dan booking batal.
                        </p>
                        <ul class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                            @foreach (['Pembeli' => $booking->buyer_confirmed_at, 'Penjual' => $booking->seller_confirmed_at] as $role => $confirmedAt)
                                <li @class(['flex items-center gap-2 rounded-xl px-3 py-2', 'bg-emerald-50 text-emerald-700' => $confirmedAt, 'bg-slate-50 text-slate-500' => ! $confirmedAt])>
                                    <x-icon :name="$confirmedAt ? 'check' : 'clock'" class="size-4" />
                                    {{ $role }}: {{ $confirmedAt ? 'tetap datang ('.$confirmedAt->format('H.i').')' : 'belum konfirmasi' }}
                                </li>
                            @endforeach
                        </ul>
                        @if ($isParty && ! $booking->hasConfirmed($user))
                            <form method="POST" action="{{ route('bookings.confirm', $booking) }}" class="mt-4">
                                @csrf
                                <button class="btn-accent w-full py-3" @disabled(! $confirmOpen)>Saya tetap datang</button>
                                @unless ($confirmOpen)
                                    <p class="mt-1.5 text-center text-xs text-slate-500">Tombol aktif mulai {{ $booking->confirmOpensAt()->translatedFormat('j M H:i') }}.</p>
                                @endunless
                            </form>
                        @endif
                    </section>

                    @if ($isParty && ! $isBuyer)
                        <section class="card p-5">
                            <h2 class="flex items-center gap-2 font-bold"><x-icon name="key" class="size-4 text-brand-600" /> Selesaikan dengan kode serah terima</h2>
                            <p class="mt-1 text-sm text-slate-600">Saat bertemu dan barang sudah dicek, minta pembeli menyebutkan 4 digit kodenya.</p>
                            <form method="POST" action="{{ route('bookings.complete', $booking) }}" class="mt-4 flex gap-2">
                                @csrf
                                <input name="code" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" required aria-label="Kode serah terima"
                                       class="field tabular w-32 text-center text-xl font-extrabold tracking-[0.4em]" placeholder="••••" @disabled(! $booking->canEnterHandoverCode())>
                                <button class="btn-primary flex-1" @disabled(! $booking->canEnterHandoverCode())>Selesai</button>
                            </form>
                            <x-field-error name="code" />
                            @unless ($booking->canEnterHandoverCode())
                                <p class="mt-1.5 text-xs text-slate-500">Bisa diisi mulai {{ $booking->confirmOpensAt()->translatedFormat('j M H:i') }}.</p>
                            @endunless
                        </section>
                    @endif

                    @if ($isParty && $past)
                        <section class="card space-y-3 p-5">
                            <h2 class="font-bold">Jadwal COD sudah lewat</h2>
                            @if ($isBuyer)
                                <form method="POST" action="{{ route('bookings.complete', $booking) }}">
                                    @csrf
                                    <button class="btn-primary w-full">Barang sudah saya terima · Tandai selesai</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('bookings.no-show', $booking) }}" x-data @submit="if (! confirm('Laporkan {{ $isBuyer ? 'penjual' : 'pembeli' }} tidak datang? Laporan ini dicatat sebagai pelanggaran pihak lain dan bisa dibantah dalam 24 jam.')) $event.preventDefault()">
                                @csrf
                                <button class="btn-ghost w-full text-red-600"><x-icon name="flag" class="size-4" /> {{ $isBuyer ? 'Penjual' : 'Pembeli' }} tidak datang</button>
                            </form>
                        </section>
                    @endif
                @endif

                @if ($status === \App\Enums\BookingStatus::Cancelled && $booking->no_show_reported_by)
                    <section class="card p-5">
                        <h2 class="flex items-center gap-2 font-bold"><x-icon name="flag" class="size-4 text-red-600" /> Laporan tidak datang</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $booking->no_show_reported_by === $booking->buyer_id ? 'Pembeli' : 'Penjual' }} melaporkan pihak lain tidak datang pada {{ $booking->no_show_reported_at->translatedFormat('j M H:i') }}.
                        </p>
                        @if ($booking->canBeDisputedBy($user))
                            <form method="POST" action="{{ route('bookings.dispute', $booking) }}" class="mt-4 space-y-2">
                                @csrf
                                <label for="dispute_note" class="field-label">Bantah laporan ini (sampai {{ $booking->disputeDeadline()->translatedFormat('j M H:i') }})</label>
                                <textarea id="dispute_note" name="dispute_note" rows="3" required minlength="10" maxlength="1000" class="field" placeholder="Jelaskan yang terjadi, misalnya: saya datang jam 10.05 dan menunggu di lobi sampai 10.30.">{{ old('dispute_note') }}</textarea>
                                <x-field-error name="dispute_note" />
                                <button class="btn-primary">Kirim bantahan ke admin</button>
                            </form>
                        @endif
                    </section>
                @endif

                @if ($status === \App\Enums\BookingStatus::Disputed)
                    <section class="card border-red-200 bg-red-50/50 p-5">
                        <h2 class="font-bold text-red-800">Sengketa sedang ditinjau admin</h2>
                        <p class="mt-1 text-sm text-red-800/80">Bantahan: “{{ $booking->dispute_note }}”</p>
                    </section>
                @endif

                @if ($booking->dispute_resolved_at)
                    <section class="card p-5">
                        <h2 class="flex items-center gap-2 font-bold"><x-icon name="scale" class="size-4 text-brand-600" /> Sengketa sudah diputuskan</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $booking->dispute_outcome === 'accepted' ? 'Bantahan diterima, pelanggaran "tidak datang" dicabut.' : 'Bantahan ditolak, pelanggaran "tidak datang" tetap berlaku.' }} Diputuskan {{ $booking->dispute_resolved_at->translatedFormat('j M Y H:i') }}.</p>
                    </section>
                @endif

                @if ($status === \App\Enums\BookingStatus::Completed)
                    <section class="card border-emerald-200 bg-emerald-50/50 p-5">
                        <h2 class="flex items-center gap-2 font-bold text-emerald-800"><x-icon name="check" class="size-4" /> Transaksi selesai</h2>
                        <p class="mt-1 text-sm text-emerald-800/80">Selesai {{ $booking->completed_at?->translatedFormat('j M Y H:i') }}{{ $booking->completed_by === $booking->buyer_id ? ' (ditandai pembeli)' : ' dengan kode serah terima' }}.</p>
                    </section>

                    @if ($canReview)
                        @php($ratingTarget = $isBuyer ? 'penjual' : 'pembeli')
                        <section class="card p-5" x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }">
                            <h2 class="flex items-center gap-2 font-bold"><x-icon name="star" class="size-4 text-accent-400" fill="currentColor" /> Beri rating untuk {{ $ratingTarget }}</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                @if ($isBuyer)
                                    Ulasanmu tampil di lapak {{ $counterpart->name }} dan membantu pembeli lain.
                                @else
                                    Rating pembeli terlihat oleh penjual berikutnya, jadi pembeli fiktif lebih mudah dikenali.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('reviews.store', $booking) }}" class="mt-4 space-y-4">
                                @csrf
                                <fieldset>
                                    <legend class="sr-only">Bintang</legend>
                                    <div class="flex gap-1" @mouseleave="hover = 0">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <label class="cursor-pointer" @mouseenter="hover = {{ $i }}">
                                                <input type="radio" name="rating" value="{{ $i }}" x-model.number="rating" class="peer sr-only" required>
                                                <span class="sr-only">{{ $i }} bintang</span>
                                                <x-icon name="star" class="size-9 transition peer-focus-visible:rounded peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400" ::class="(hover || rating) >= {{ $i }} ? 'text-accent-400 fill-current' : 'text-slate-300'" />
                                            </label>
                                        @endfor
                                    </div>
                                    <x-field-error name="rating" />
                                </fieldset>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($reviewTags as $tag)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="tags[]" value="{{ $tag }}" class="peer sr-only" @checked(in_array($tag, old('tags', []), true))>
                                            <span class="chip peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">{{ $tag }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div>
                                    <label for="review-comment" class="field-label">Ulasan <span class="font-normal text-slate-400">(opsional)</span></label>
                                    <textarea id="review-comment" name="comment" rows="3" maxlength="500" class="field" placeholder="{{ $isBuyer ? 'Contoh: barang sesuai foto, penjual datang tepat waktu.' : 'Contoh: pembeli datang tepat waktu dan ramah.' }}">{{ old('comment') }}</textarea>
                                    <x-field-error name="comment" />
                                </div>
                                <button class="btn-primary w-full sm:w-auto" :disabled="rating === 0">Kirim rating</button>
                            </form>
                        </section>
                    @endif

                    @if ($booking->reviews->isNotEmpty())
                        <section class="card p-5">
                            <h2 class="font-bold">Rating transaksi ini</h2>
                            <ul class="mt-3 space-y-3">
                                @foreach ($booking->reviews as $review)
                                    <li class="rounded-xl bg-slate-50 p-3 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-ink">{{ $review->reviewer->name }}</span>
                                            <span class="text-xs text-slate-500">{{ $review->direction->label() }}</span>
                                            <span class="ml-auto flex text-accent-400" aria-label="{{ $review->rating }} dari 5 bintang">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <x-icon name="star" class="size-3.5" :fill="$i <= $review->rating ? 'currentColor' : 'none'" />
                                                @endfor
                                            </span>
                                        </div>
                                        @if ($review->comment)<p class="mt-1 text-slate-600">{{ $review->comment }}</p>@endif
                                        @if ($review->tags)
                                            <p class="mt-1.5 flex flex-wrap gap-1">
                                                @foreach ($review->tags as $tag)<span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-brand-700 ring-1 ring-brand-100">{{ $tag }}</span>@endforeach
                                            </p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($isParty)
                        <p class="text-center text-sm text-slate-500">Bagaimana pengalamanmu memakai Jualin Aja ITK? <button type="button" x-data @click="$dispatch('open-feedback')" class="font-semibold text-brand-600 hover:underline">Beri masukan</button></p>
                    @endif
                @endif
            </div>

            <aside class="space-y-5">
                {{-- Kode serah terima: hanya pembeli yang melihat --}}
                @if ($handoverCode && $status === \App\Enums\BookingStatus::Accepted)
                    <section class="relative overflow-hidden rounded-2xl bg-ink p-5 text-white">
                        <div class="absolute -right-6 -top-6 size-20 rounded-full bg-accent-400"></div>
                        <p class="relative text-xs font-semibold uppercase tracking-wider text-slate-300">Kode serah terima</p>
                        <p x-data="{ show: false }" class="relative mt-2 flex items-center gap-3">
                            <span class="tabular text-4xl font-extrabold tracking-[0.3em]" x-text="show ? @js($handoverCode) : '••••'">••••</span>
                            <button type="button" @click="show = ! show" class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold hover:bg-white/20" x-text="show ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</button>
                        </p>
                        <p class="relative mt-3 text-xs text-slate-300">Sebutkan kode ini ke penjual <strong>setelah</strong> barang kamu cek. Jangan bagikan sebelum bertemu.</p>
                    </section>
                @endif

                <section class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $isBuyer ? 'Penjual' : 'Pembeli' }}</p>
                    <div class="mt-3 flex items-center gap-3">
                        <x-avatar :user="$counterpart" size="size-11" />
                        <div class="min-w-0">
                            <p class="truncate font-bold text-ink">{{ $counterpart->name }}</p>
                            <p class="text-xs text-slate-500">{{ $counterpart->prodi ?? 'Mahasiswa ITK' }}{{ $counterpart->angkatan ? ' · '.$counterpart->angkatan : '' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <x-reliability-badge :reliability="$counterpartReliability" :new-label="$isBuyer ? 'Penjual baru' : 'Pembeli baru'" />
                        @if ($counterpartRating['count'] > 0)
                            <span class="inline-flex items-center gap-0.5 font-semibold text-ink"><x-icon name="star" class="size-3.5 text-accent-400" fill="currentColor" /> {{ number_format($counterpartRating['average'], 1, ',', '') }} <span class="font-normal text-slate-400">({{ $counterpartRating['count'] }} ulasan sebagai {{ $isBuyer ? 'penjual' : 'pembeli' }})</span></span>
                        @endif
                    </div>
                    @if ($isParty && $booking->isActive())
                        <form method="POST" action="{{ route('bookings.whatsapp', $booking) }}" target="_blank" class="mt-4">
                            @csrf
                            <button class="btn-ghost w-full"><x-icon name="message-circle" class="size-4 text-emerald-600" /> Chat WhatsApp</button>
                        </form>
                    @endif
                </section>

                @if ($isParty && $booking->isActive())
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" x-data
                          @submit="if (! confirm(@js($status === \App\Enums\BookingStatus::Accepted && now()->gte($booking->cod_at->copy()->subHours(config('jualin.late_cancel_hours'))) ? 'Jadwal kurang dari 3 jam lagi. Membatalkan sekarang dicatat sebagai pelanggaran. Tetap batalkan?' : 'Batalkan booking ini?'))) $event.preventDefault()">
                        @csrf @method('PATCH')
                        <button class="w-full rounded-full px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600">Batalkan booking</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
