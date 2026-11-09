<x-app-layout>
    <x-slot:title>Sengketa</x-slot:title>

    <x-page-header title="Sengketa & pelanggaran" subtitle="Putuskan bantahan laporan “tidak datang” dan tinjau akun yang mencapai batas pelanggaran." />
    @include('admin._nav')

    <section>
        <h2 class="mb-3 text-lg font-extrabold">Bantahan menunggu keputusan <span class="text-sm font-semibold text-slate-400">{{ $disputes->count() }}</span></h2>
        @forelse ($disputes as $booking)
            @php
                $reporter = $booking->no_show_reported_by === $booking->buyer_id ? $booking->buyer : $booking->seller;
                $disputer = $booking->no_show_reported_by === $booking->buyer_id ? $booking->seller : $booking->buyer;
            @endphp
            <article class="card mb-3 p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('bookings.show', $booking) }}" class="font-bold text-ink hover:text-brand-600">{{ $booking->code }} · {{ $booking->product->name }}</a>
                    <x-status-badge :status="$booking->status" />
                </div>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-500">Jadwal COD</dt><dd class="font-semibold">{{ $booking->cod_at->translatedFormat('j M Y H:i') }} · {{ $booking->codLocation->name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Pelapor "tidak datang"</dt><dd class="font-semibold">{{ $reporter->name }} ({{ $booking->no_show_reported_by === $booking->buyer_id ? 'pembeli' : 'penjual' }})</dd></div>
                    <div><dt class="text-xs text-slate-500">Membantah</dt><dd class="font-semibold">{{ $disputer->name }} · {{ $booking->updated_at->diffForHumans() }}</dd></div>
                </dl>
                <p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">“{{ $booking->dispute_note }}”</p>
                <p class="mt-2 text-xs text-slate-500">Status konfirmasi: pembeli {{ $booking->buyer_confirmed_at ? 'konfirmasi '.$booking->buyer_confirmed_at->format('H.i') : 'tidak konfirmasi' }}, penjual {{ $booking->seller_confirmed_at ? 'konfirmasi '.$booking->seller_confirmed_at->format('H.i') : 'tidak konfirmasi' }}.</p>
                <form method="POST" action="{{ route('admin.disputes.update', $booking) }}" class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center">
                    @csrf @method('PATCH')
                    <input name="note" maxlength="500" class="field flex-1" placeholder="Catatan untuk kedua pihak (opsional)" aria-label="Catatan keputusan">
                    <div class="flex gap-2">
                        <button name="outcome" value="accepted" class="btn-primary px-3.5 py-2">Terima bantahan</button>
                        <button name="outcome" value="rejected" class="btn-ghost px-3.5 py-2">Tolak bantahan</button>
                    </div>
                </form>
            </article>
        @empty
            <x-empty-state icon="check" title="Tidak ada sengketa" description="Semua bantahan sudah diputuskan." />
        @endforelse
    </section>

    <section class="mt-10">
        <h2 class="mb-1 text-lg font-extrabold">Akun perlu ditinjau</h2>
        <p class="mb-3 text-sm text-slate-500">Akun dengan {{ max(array_keys(config('jualin.sanctions'))) }}+ pelanggaran aktif dalam {{ config('jualin.violation_window_days') }} hari, atau yang sedang nonaktif.</p>
        @forelse ($flaggedUsers as $flagged)
            <article class="card mb-3 p-5">
                <div class="flex flex-wrap items-center gap-3">
                    <x-avatar :user="$flagged" size="size-11" />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-ink">{{ $flagged->name }} @unless ($flagged->is_active)<span class="ml-1 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-700">Nonaktif</span>@endunless</p>
                        <p class="text-xs text-slate-500">{{ $flagged->email }} · {{ $flagged->recent_violations_count }} pelanggaran aktif{{ $flagged->isBookingBlocked() ? ' · tidak bisa booking sampai '.$flagged->booking_blocked_until->translatedFormat('j M H:i') : '' }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.users.toggle', $flagged) }}">
                        @csrf @method('PATCH')
                        <button class="{{ $flagged->is_active ? 'btn-ghost text-red-600' : 'btn-primary' }} px-3.5 py-2">{{ $flagged->is_active ? 'Nonaktifkan akun' : 'Aktifkan lagi' }}</button>
                    </form>
                </div>
                @if ($flagged->violations->isNotEmpty())
                    <ul class="mt-4 divide-y divide-slate-100 rounded-xl bg-slate-50 text-sm">
                        @foreach ($flagged->violations as $violation)
                            <li class="flex flex-wrap items-center gap-2 px-3 py-2">
                                <span class="font-semibold text-ink">{{ $violation->type->label() }}</span>
                                <span class="text-xs text-slate-500">{{ $violation->booking->code }} · {{ $violation->booking->product->name }} · {{ $violation->created_at->translatedFormat('j M') }} · {{ $violation->status->label() }}</span>
                                <form method="POST" action="{{ route('admin.violations.revoke', $violation) }}" class="ml-auto">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-semibold text-brand-600 hover:underline">Cabut</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @empty
            <p class="card p-5 text-sm text-slate-500">Tidak ada akun yang perlu ditinjau.</p>
        @endforelse
    </section>

    @if ($resolved->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-3 text-lg font-extrabold">Keputusan terakhir</h2>
            <ul class="card divide-y divide-slate-100 text-sm">
                @foreach ($resolved as $booking)
                    <li class="flex flex-wrap items-center gap-2 px-4 py-3">
                        <a href="{{ route('bookings.show', $booking) }}" class="font-semibold text-ink hover:text-brand-600">{{ $booking->code }} · {{ $booking->product->name }}</a>
                        <span @class(['rounded-full px-2 py-0.5 text-[11px] font-bold', 'bg-emerald-50 text-emerald-700' => $booking->dispute_outcome === 'accepted', 'bg-slate-100 text-slate-600' => $booking->dispute_outcome !== 'accepted'])>{{ $booking->dispute_outcome === 'accepted' ? 'Bantahan diterima' : 'Bantahan ditolak' }}</span>
                        <span class="ml-auto text-xs text-slate-400">{{ $booking->dispute_resolved_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
