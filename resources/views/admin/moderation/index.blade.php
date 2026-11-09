<x-app-layout>
    <x-slot:title>Moderasi</x-slot:title>

    <x-page-header title="Antrean moderasi" subtitle="Setujui barang yang layak, atau kembalikan ke penjual dengan catatan." />
    @include('admin._nav')

    @if ($products->isEmpty())
        <x-empty-state icon="check" title="Antrean kosong" description="Belum ada barang baru yang menunggu moderasi." />
    @else
        <div class="space-y-4">
            @foreach ($products as $product)
                <article class="card grid gap-5 p-4 sm:p-5 lg:grid-cols-[280px_1fr_300px]">
                    <div class="grid grid-cols-3 gap-2 self-start">
                        @foreach ($product->images as $image)
                            <a href="{{ $image->url }}" target="_blank" @class(['overflow-hidden rounded-xl bg-slate-100', 'col-span-3 aspect-[4/3]' => $loop->first, 'aspect-square' => ! $loop->first])>
                                <img src="{{ $image->url }}" alt="" class="size-full object-cover">
                            </a>
                        @endforeach
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-600">{{ $product->category->name }} · {{ $product->condition->label() }}</p>
                        <h2 class="mt-1 text-lg font-extrabold">{{ $product->name }}</h2>
                        <p class="tabular font-extrabold text-brand-600">{{ $product->formatted_price }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $product->description }}</p>
                        <p class="mt-3 text-xs text-slate-500">
                            Oleh <span class="font-semibold text-ink">{{ $product->seller->name }}</span> ({{ $product->seller->nim }}) ·
                            {{ $product->cod_areas_label }} · masuk {{ $product->updated_at->diffForHumans() }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.moderation.update', $product) }}" class="flex flex-col gap-3 rounded-2xl bg-slate-50 p-4" x-data="{ note: '' }">
                        @csrf @method('PATCH')
                        <label for="note-{{ $product->id }}" class="text-sm font-bold text-ink">Catatan untuk penjual</label>
                        <textarea id="note-{{ $product->id }}" name="note" rows="3" x-model="note" maxlength="500" class="field text-sm" placeholder="Wajib jika minta perbaikan. Contoh: foto kurang jelas, tambah foto bagian belakang."></textarea>
                        @if ($errors->has('note') && old('_product') == $product->id)
                            <p class="text-xs text-red-600">{{ $errors->first('note') }}</p>
                        @endif
                        <input type="hidden" name="_product" value="{{ $product->id }}">
                        <div class="mt-auto grid grid-cols-2 gap-2">
                            <button name="decision" value="needs_revision" :disabled="note.trim() === ''" class="btn-ghost px-3 text-red-600">Perlu perbaikan</button>
                            <button name="decision" value="approved" class="btn-primary px-3"><x-icon name="check" class="size-4" /> Setujui</button>
                        </div>
                    </form>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-app-layout>
