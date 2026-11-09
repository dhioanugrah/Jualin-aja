<x-app-layout>
    <x-slot:title>Titik COD</x-slot:title>

    <x-page-header title="Titik COD" subtitle="Pembeli hanya bisa memilih titik aktif di daftar ini, tidak bisa mengetik alamat bebas." />
    @include('admin._nav')

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            @foreach ($areas as $area)
                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-extrabold">
                        <x-icon :name="$area === \App\Enums\CodArea::Inside ? 'shield-check' : 'map-pin'" class="text-brand-600" /> {{ $area->label() }}
                        <span class="text-sm font-semibold text-slate-400">{{ ($locations[$area->value] ?? collect())->count() }}</span>
                    </h2>
                    <div class="card divide-y divide-slate-100 overflow-hidden">
                        @forelse ($locations[$area->value] ?? [] as $location)
                            <form method="POST" action="{{ route('admin.cod-locations.update', $location) }}" x-data="{ editing: false }" @class(['p-4', 'bg-slate-50/80' => ! $location->is_active])>
                                @csrf @method('PUT')
                                <div x-show="! editing" class="flex flex-wrap items-center gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p @class(['font-bold', 'text-ink' => $location->is_active, 'text-slate-400 line-through' => ! $location->is_active])>{{ $location->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $location->bookings_count }} booking
                                            @if ($location->maps_url)· <a href="{{ $location->maps_url }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Maps</a>@endif
                                            @unless ($location->is_active)· nonaktif @endunless
                                        </p>
                                    </div>
                                    <button type="button" @click="editing = true" class="btn-ghost px-3 py-1.5 text-xs"><x-icon name="pencil" class="size-3.5" /> Ubah</button>
                                </div>
                                <div x-cloak x-show="editing" class="grid gap-2 sm:grid-cols-2">
                                    <input name="name" value="{{ $location->name }}" required maxlength="120" class="field" aria-label="Nama titik">
                                    <select name="area" class="field" aria-label="Area">
                                        @foreach ($areas as $option)
                                            <option value="{{ $option->value }}" @selected($location->area === $option)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                    <input name="maps_url" type="url" value="{{ $location->maps_url }}" placeholder="https://maps.google.com/…" class="field sm:col-span-2" aria-label="Tautan Google Maps">
                                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($location->is_active) class="rounded border-slate-300 text-brand-600"> Aktif</label>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="editing = false" class="btn-ghost px-4 py-2">Batal</button>
                                        <button class="btn-primary px-4 py-2">Simpan</button>
                                    </div>
                                </div>
                            </form>
                        @empty
                            <p class="p-4 text-sm text-slate-500">Belum ada titik di area ini.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <aside>
            <form method="POST" action="{{ route('admin.cod-locations.store') }}" class="card space-y-4 p-5 lg:sticky lg:top-24">
                @csrf
                <h2 class="font-extrabold">Tambah titik</h2>
                <div>
                    <label for="name" class="field-label">Nama titik</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="field" placeholder="Lobi Gedung Kuliah Bersama">
                    <x-field-error name="name" />
                </div>
                <div>
                    <label for="area" class="field-label">Area</label>
                    <select id="area" name="area" class="field">
                        @foreach ($areas as $area)
                            <option value="{{ $area->value }}" @selected(old('area') === $area->value)>{{ $area->label() }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="area" />
                </div>
                <div>
                    <label for="maps_url" class="field-label">Tautan Google Maps <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="maps_url" name="maps_url" type="url" value="{{ old('maps_url') }}" class="field" placeholder="https://maps.google.com/…">
                    <x-field-error name="maps_url" />
                </div>
                <p class="text-xs text-slate-500">Pilih tempat ramai dan terang. Titik yang sudah dipakai booking tidak dihapus, cukup dinonaktifkan.</p>
                <button class="btn-primary w-full">Tambah</button>
            </form>
        </aside>
    </div>
</x-app-layout>
