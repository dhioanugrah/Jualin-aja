@php
    $existingPhotos = $product->exists
        ? $product->images->map(fn ($image) => ['id' => $image->id, 'url' => $image->url])->values()
        : collect();
    $selectedAreas = collect(old('cod_areas', $product->cod_areas?->map->value->all() ?? ['inside']));
@endphp

<div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
    {{-- Foto --}}
    <section class="card p-5 sm:p-6 lg:row-span-2"
             x-data="photoUploader({ max: {{ config('jualin.max_photos') }}, existing: @js($existingPhotos) })">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="flex items-center gap-1 font-bold">Foto barang <x-help-link chapter="penjual" label="Tips memotret dan mengisi form" /></h2>
                <p class="text-xs text-slate-500">1–{{ config('jualin.max_photos') }} foto. Foto pertama jadi sampul. Otomatis dikompres agar hemat kuota.</p>
            </div>
            <span class="tabular rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600" x-text="`${items.length}/${max}`"></span>
        </div>

        <input type="file" x-ref="upload" name="photos[]" multiple class="hidden" aria-hidden="true" tabindex="-1">
        <input type="file" x-ref="camera" accept="image/*" capture="environment" class="hidden" @change="addFiles($event.target.files)">
        <input type="file" x-ref="gallery" accept="image/*" multiple class="hidden" @change="addFiles($event.target.files)">
        <template x-for="item in items" :key="item.url">
            <input type="hidden" name="photo_order[]" :value="item.order">
        </template>

        {{-- Area unggah: klik, seret-lepas (desktop), atau tombol kamera/galeri --}}
        <div x-show="remaining > 0"
             @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
             @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
             :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-slate-300 bg-slate-50/60'"
             class="rounded-2xl border-2 border-dashed px-4 py-8 text-center transition">
            <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-white text-brand-600 shadow-card">
                <x-icon name="camera" class="size-6" />
            </div>
            <p class="mt-3 text-sm font-semibold text-ink">Tambah foto barang</p>
            <p class="text-xs text-slate-500"><span class="pointer-coarse:hidden">Seret foto ke sini, atau </span>pilih salah satu:</p>

            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <button type="button" @click="$refs.camera.click()" class="btn-primary">
                    <x-icon name="camera" class="size-4" /> Ambil Foto
                </button>
                <button type="button" @click="$refs.gallery.click()" class="btn-ghost">
                    <x-icon name="image" class="size-4" /> Pilih dari Galeri
                </button>
            </div>
            <p x-show="busy" x-cloak class="mt-3 text-xs font-semibold text-brand-600">Mengompres foto…</p>
        </div>

        <p x-show="error" x-cloak x-text="error" class="mt-3 text-xs font-medium text-red-600"></p>
        <x-field-error name="photos" />
        <x-field-error name="photos.*" />
        @if ($errors->any() && ! $errors->has('photos'))
            <p class="mt-3 text-xs text-amber-700">Foto baru yang tadi dipilih perlu dipilih ulang setelah ada kesalahan isian.</p>
        @endif

        {{-- Pratinjau --}}
        <ul class="mt-4 grid grid-cols-3 gap-3" x-show="items.length" x-cloak>
            <template x-for="(item, index) in items" :key="item.url">
                <li class="group relative aspect-square overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                    <img :src="item.url" alt="" class="size-full object-cover">
                    <span x-show="index === 0" class="absolute left-1.5 top-1.5 rounded-full bg-accent-400 px-2 py-0.5 text-[10px] font-extrabold text-ink">Sampul</span>
                    <button type="button" @click="remove(index)" class="absolute right-1.5 top-1.5 flex size-7 items-center justify-center rounded-full bg-ink/70 text-white hover:bg-ink" aria-label="Hapus foto">
                        <x-icon name="x" class="size-4" />
                    </button>
                    <div class="absolute inset-x-1.5 bottom-1.5 flex justify-between">
                        <button type="button" @click="move(index, -1)" x-show="index > 0" class="flex size-7 items-center justify-center rounded-full bg-white/90 text-ink shadow" aria-label="Geser ke kiri"><x-icon name="chevron-left" class="size-4" /></button>
                        <button type="button" @click="makeCover(index)" x-show="index > 0" class="rounded-full bg-white/90 px-2 text-[10px] font-bold text-ink shadow">Jadikan sampul</button>
                        <button type="button" @click="move(index, 1)" x-show="index < items.length - 1" class="ml-auto flex size-7 items-center justify-center rounded-full bg-white/90 text-ink shadow" aria-label="Geser ke kanan"><x-icon name="chevron-right" class="size-4" /></button>
                    </div>
                </li>
            </template>
        </ul>

        <div class="mt-5 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
            <p class="font-bold">Tips foto yang cepat laku</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                <li>Foto di tempat terang, latar polos.</li>
                <li>Ambil dari 3 sudut, termasuk bagian yang cacat jika ada.</li>
            </ul>
        </div>
    </section>

    {{-- Info barang --}}
    <section class="card space-y-5 p-5 sm:p-6">
        <h2 class="font-bold">Info barang</h2>

        <div>
            <label for="name" class="field-label">Nama barang</label>
            <input id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="80" class="field" placeholder="Contoh: Kalkulus Purcell Jilid 1">
            <x-field-error name="name" />
        </div>

        <div>
            <label for="category_id" class="field-label">Kategori</label>
            <select id="category_id" name="category_id" required class="field">
                <option value="" disabled @selected(! old('category_id', $product->category_id))>Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-field-error name="category_id" />
        </div>

        <fieldset>
            <legend class="field-label">Kondisi</legend>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($conditions as $condition)
                    <label class="cursor-pointer">
                        <input type="radio" name="condition" value="{{ $condition->value }}" class="peer sr-only" @checked(old('condition', $product->condition?->value ?? 'good') === $condition->value)>
                        <span class="flex justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100">{{ $condition->label() }}</span>
                    </label>
                @endforeach
            </div>
            <x-field-error name="condition" />
        </fieldset>

        <div>
            <label for="description" class="field-label">Deskripsi</label>
            <textarea id="description" name="description" rows="5" required maxlength="2000" class="field" placeholder="Ceritakan kondisi, kelengkapan, alasan dijual…">{{ old('description', $product->description) }}</textarea>
            <x-field-error name="description" />
        </div>
    </section>

    {{-- Harga & COD --}}
    <section class="card space-y-5 p-5 sm:p-6" x-data="{ donation: @js((bool) old('is_donation', $product->is_donation)) }">
        <h2 class="font-bold">Harga & penyerahan</h2>

        <div>
            <label for="price" class="field-label">Harga</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                <input id="price" name="price" inputmode="numeric" value="{{ old('price', $product->exists ? $product->price : '') }}" :disabled="donation" :required="! donation" class="field tabular pl-11 disabled:bg-slate-100 disabled:text-slate-400" :placeholder="donation ? 'Gratis' : '50000'">
            </div>
            <x-field-error name="price" />
            <label class="mt-3 flex items-center gap-2.5 text-sm text-slate-600">
                <input type="hidden" name="is_donation" value="0">
                <input type="checkbox" name="is_donation" value="1" x-model="donation" class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span><span class="font-semibold text-ink">Donasi</span>, berikan gratis ke adik tingkat</span>
            </label>
        </div>

        <fieldset>
            <legend class="field-label">Metode penyerahan</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($handoverMethods as $method)
                    <label class="cursor-pointer">
                        <input type="radio" name="handover_method" value="{{ $method->value }}" class="peer sr-only" @checked(old('handover_method', $product->handover_method?->value ?? 'cod') === $method->value)>
                        <span class="flex justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700">{{ $method->label() }}</span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-slate-500">"Pengiriman" hanya label info, tidak ada hitung ongkir.</p>
        </fieldset>

        <fieldset>
            <legend class="field-label">Area COD yang kamu layani</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($codAreas as $area)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="cod_areas[]" value="{{ $area->value }}" class="peer sr-only" @checked($selectedAreas->contains($area->value))>
                        <span class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition peer-checked:border-brand-600 peer-checked:bg-brand-50">
                            <x-icon name="map-pin" class="mt-0.5 size-4 text-brand-600" />
                            <span>
                                <span class="block text-sm font-bold text-ink">{{ $area->label() }}</span>
                                <span class="block text-xs text-slate-500">{{ $area->value === 'inside' ? 'Titik ramai di kampus. Disarankan.' : 'Titik umum di luar kampus yang dipilih admin.' }}</span>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-field-error name="cod_areas" />
        </fieldset>

        <div>
            <label for="location" class="field-label">Lokasi umum <span class="font-normal text-slate-400">(opsional)</span></label>
            <input id="location" name="location" value="{{ old('location', $product->location) }}" maxlength="100" class="field" placeholder="Contoh: Asrama ITK">
            <x-field-error name="location" />
        </div>
    </section>
</div>
