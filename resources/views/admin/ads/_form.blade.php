@php($editing = $ad->exists)
<div>
    <label for="title" class="field-label">Judul</label>
    <input id="title" name="title" value="{{ old('title', $ad->title) }}" required maxlength="80" class="field" placeholder="Open Recruitment HMIF 2026">
    <x-field-error name="title" />
</div>
<div>
    <label for="advertiser" class="field-label">Pemasang iklan</label>
    <input id="advertiser" name="advertiser" value="{{ old('advertiser', $ad->advertiser) }}" required maxlength="80" class="field" placeholder="Himpunan, UKM, atau usaha">
    <x-field-error name="advertiser" />
</div>
<div>
    <label for="url" class="field-label">Tautan <span class="font-normal text-slate-400">(opsional)</span></label>
    <input id="url" name="url" type="url" value="{{ old('url', $ad->url) }}" maxlength="255" class="field" placeholder="https://…">
    <x-field-error name="url" />
</div>
<div>
    <label for="placement" class="field-label">Posisi</label>
    <select id="placement" name="placement" class="field">
        @foreach ($placements as $placement)
            <option value="{{ $placement->value }}" @selected(old('placement', $ad->placement?->value) === $placement->value)>{{ $placement->label() }}</option>
        @endforeach
    </select>
    <x-field-error name="placement" />
</div>
<div class="grid grid-cols-2 gap-2">
    <div>
        <label for="starts_at" class="field-label">Mulai</label>
        <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', $ad->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="field">
        <x-field-error name="starts_at" />
    </div>
    <div>
        <label for="ends_at" class="field-label">Selesai</label>
        <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', $ad->ends_at?->format('Y-m-d') ?? now()->addDays(14)->format('Y-m-d')) }}" required class="field">
        <x-field-error name="ends_at" />
    </div>
</div>
<div>
    <label for="image" class="field-label">Gambar {{ $editing ? '(kosongkan jika tidak diganti)' : '' }}</label>
    @if ($editing)
        <img src="{{ $ad->image_url }}" alt="" class="mb-2 aspect-[16/5] w-full rounded-xl object-cover">
    @endif
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" @required(! $editing) class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700">
    <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP maks. {{ config('jualin.ad_max_image_kb') / 1024 }} MB. Rasio 16:5 (misalnya 1600×500).</p>
    <x-field-error name="image" />
</div>
<label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ad->exists ? $ad->is_active : true)) class="rounded border-slate-300 text-brand-600"> Aktif</label>
