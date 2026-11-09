{{-- Tombol Bagikan (F15): tautan, WhatsApp, dan kartu gambar berlogo untuk status WA/IG. --}}
<div x-data="shareProduct(@js([
        'name' => $product->name,
        'slug' => $product->slug,
        'price' => $product->formatted_price,
        'image' => $product->images->first()?->url,
        'seller' => $product->seller->name,
        'area' => $product->cod_areas_label,
        'url' => route('products.show', $product),
        'logo' => asset('brand/logo.svg'),
    ]))">
    <button type="button" @click="openSheet()" class="btn-ghost w-full"><x-icon name="share" class="size-4" /> Bagikan</button>

    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="bagikan-judul" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="open = false"></div>
        <div x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-4" x-transition:enter-end="translate-y-0 opacity-100"
             class="relative max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-float sm:max-w-md sm:rounded-3xl sm:p-6">
            <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-slate-200 sm:hidden"></div>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="bagikan-judul" class="text-lg font-extrabold">Bagikan barang</h2>
                    <p class="text-sm text-slate-500">Promosikan ke status WA atau story IG.</p>
                </div>
                <button type="button" @click="open = false" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-ink" aria-label="Tutup"><x-icon name="x" /></button>
            </div>

            <div class="mx-auto aspect-[4/5] w-48 overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200">
                <template x-if="cardUrl"><img :src="cardUrl" alt="Kartu bagikan {{ $product->name }}" class="size-full object-cover"></template>
                <div x-show="! cardUrl" class="flex size-full items-center justify-center text-xs text-slate-400">Menyiapkan kartu…</div>
            </div>
            <a x-ref="download" :href="cardUrl" download="jualin-{{ $product->slug }}.png" class="hidden">Unduh</a>

            <div class="mt-5 grid gap-2">
                <button type="button" @click="shareCard()" class="btn-primary w-full" :disabled="busy"><x-icon name="image" class="size-4" /> Bagikan kartu gambar</button>
                <a :href="waLink" target="_blank" rel="noopener" class="btn-ghost w-full"><x-icon name="message-circle" class="size-4 text-emerald-600" /> Kirim tautan lewat WhatsApp</a>
                <button type="button" @click="copyLink()" class="btn-ghost w-full"><x-icon name="copy" class="size-4" /> <span x-text="copied ? 'Tautan tersalin!' : 'Salin tautan'">Salin tautan</span></button>
            </div>
        </div>
    </div>
</div>
