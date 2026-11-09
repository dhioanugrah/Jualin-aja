<x-app-layout>
    <x-slot:title>Booking COD · {{ $product->name }}</x-slot:title>

    @php
        $max = config('jualin.max_active_bookings');
        $areas = $product->cod_areas;
        $defaultArea = old('cod_area', $areas->first()?->value);
        $locationsJson = $locations->map(fn ($group) => $group->map(fn ($location) => ['id' => $location->id, 'name' => $location->name, 'maps' => $location->maps_url])->values());
        $minCod = now()->addHours(config('jualin.cod_min_lead_hours'))->addMinutes(5)->format('Y-m-d\TH:i');
        $maxCod = now()->addDays(14)->format('Y-m-d\TH:i');
    @endphp

    <div class="mx-auto max-w-2xl">
        <x-page-header title="Ajukan Booking COD" subtitle="Kunci waktu dan titik bertemu. Penjual punya {{ config('jualin.booking_respond_hours') }} jam untuk menerima." :back="route('products.show', $product)">
            <x-slot:actions><a href="{{ route('guide.show', 'cod-aman') }}" target="_blank" class="btn-ghost px-3.5 py-2"><x-icon name="help" class="size-4" /> Panduan COD aman</a></x-slot:actions>
        </x-page-header>

        <div class="card mb-5 flex items-center gap-3 p-3">
            <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                @if ($product->cover_url)<img src="{{ $product->cover_url }}" alt="" class="size-full object-cover">@endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate font-bold text-ink">{{ $product->name }}</p>
                <p class="text-xs text-slate-500">dari {{ $product->seller->name }}</p>
            </div>
            <div class="text-right">
                @if ($offer)
                    <p class="text-xs text-slate-400 line-through">{{ $product->formatted_price }}</p>
                    <p class="tabular font-extrabold text-brand-600">{{ $offer->formatted_price }}</p>
                @else
                    <p class="tabular font-extrabold text-brand-600">{{ $product->formatted_price }}</p>
                @endif
            </div>
        </div>

        @if ($isBlocked)
            <div class="mb-5 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <x-icon name="ban" class="mt-0.5" />
                <p>Akunmu tidak bisa booking sampai <strong>{{ auth()->user()->booking_blocked_until->translatedFormat('j F Y H:i') }}</strong> karena pelanggaran. Kamu masih bisa melihat barang dan menghubungi penjual.</p>
            </div>
        @elseif ($activeBookings >= $max)
            <div class="mb-5 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <x-icon name="alert" class="mt-0.5" />
                <p>Kamu sudah punya {{ $activeBookings }} booking aktif (maksimal {{ $max }}). Selesaikan atau batalkan dulu di <a href="{{ route('inbox', ['tab' => 'keluar']) }}" class="font-bold underline">Pesanan</a>.</p>
            </div>
        @endif

        @error('booking')
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror
        @error('offer_id')
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('bookings.store', $product) }}" class="card space-y-6 p-5 sm:p-6"
              x-data="{ area: @js($defaultArea), location: @js((string) old('cod_location_id', '')), locations: @js($locationsJson) }">
            @csrf
            @if ($offer)
                <input type="hidden" name="offer_id" value="{{ $offer->id }}">
            @endif

            <fieldset>
                <legend class="field-label">Area COD</legend>
                <p class="-mt-1 mb-2 text-xs text-slate-500">Hanya area yang dilayani penjual.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($areas as $area)
                        <label class="cursor-pointer">
                            <input type="radio" name="cod_area" value="{{ $area->value }}" x-model="area" @change="location = ''" class="peer sr-only" required>
                            <span class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 transition peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-100 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">
                                <x-icon :name="$area === \App\Enums\CodArea::Inside ? 'shield-check' : 'map-pin'" class="text-brand-600" />
                                <span>
                                    <span class="block font-bold text-ink">{{ $area->label() }}</span>
                                    <span class="block text-xs text-slate-500">{{ $area === \App\Enums\CodArea::Inside ? 'Titik ramai di kampus · Disarankan' : 'Titik umum pilihan admin' }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-field-error name="cod_area" />
            </fieldset>

            <div>
                <label for="cod_location_id" class="field-label">Titik COD</label>
                <select id="cod_location_id" name="cod_location_id" x-model="location" required class="field">
                    <option value="">Pilih titik…</option>
                    <template x-for="item in (locations[area] ?? [])" :key="item.id">
                        <option :value="String(item.id)" x-text="item.name" :selected="String(item.id) === location"></option>
                    </template>
                </select>
                <template x-for="item in (locations[area] ?? [])" :key="'maps-' + item.id">
                    <a x-show="String(item.id) === location && item.maps" :href="item.maps" target="_blank" rel="noopener" class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline">
                        <x-icon name="map-pin" class="size-3.5" /> Lihat di Google Maps
                    </a>
                </template>
                <p x-show="(locations[area] ?? []).length === 0" class="mt-1.5 text-xs text-amber-700">Belum ada titik aktif untuk area ini. Hubungi admin.</p>
                <x-field-error name="cod_location_id" />
            </div>

            <div x-show="area === 'around'" x-transition class="flex gap-3 rounded-2xl border border-accent-300/60 bg-accent-50 p-4 text-sm text-ink">
                <x-icon name="shield-check" class="mt-0.5 text-accent-500" />
                <div>
                    <p class="font-bold">Tips COD di sekitar ITK</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-slate-600">
                        <li>Ajak teman dan bertemu di tempat ramai.</li>
                        <li>Cek barang sebelum membayar. Jangan transfer uang muka.</li>
                    </ul>
                </div>
            </div>

            <div>
                <label for="cod_at" class="field-label">Waktu COD</label>
                <input id="cod_at" name="cod_at" type="datetime-local" value="{{ old('cod_at') }}" min="{{ $minCod }}" max="{{ $maxCod }}" required class="field tabular">
                <p class="mt-1.5 text-xs text-slate-500">Jam {{ sprintf('%02d.00–%02d.00', config('jualin.cod_open_hour'), config('jualin.cod_close_hour')) }} WITA, paling cepat {{ config('jualin.cod_min_lead_hours') }} jam dari sekarang.</p>
                <x-field-error name="cod_at" />
            </div>

            <div>
                <label for="note" class="field-label">Catatan untuk penjual <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea id="note" name="note" rows="2" maxlength="500" class="field" placeholder="Contoh: saya pakai jaket biru, tunggu di depan pintu masuk.">{{ old('note') }}</textarea>
                <x-field-error name="note" />
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                <p class="mb-1 font-bold text-ink">Yang terjadi setelah ini</p>
                <ol class="list-decimal space-y-0.5 pl-4">
                    <li>Kamu mendapat kode booking dan <strong>kode serah terima 4 digit</strong> yang hanya kamu yang lihat.</li>
                    <li>3 jam sebelum COD, kamu dan penjual menekan <strong>“Saya tetap datang”</strong>. Belum konfirmasi sampai 1 jam sebelum jadwal = booking batal dan tercatat pelanggaran.</li>
                    <li>Saat bertemu, sebutkan kode serah terima ke penjual untuk menyelesaikan transaksi.</li>
                </ol>
            </div>

            <button class="btn-accent w-full py-3" @disabled($isBlocked || $activeBookings >= $max)>Kirim booking</button>
        </form>
    </div>
</x-app-layout>
