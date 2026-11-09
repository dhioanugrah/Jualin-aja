<x-app-layout>
    <x-slot:title>Ubah iklan</x-slot:title>

    <x-page-header title="Ubah iklan" :subtitle="$ad->title" :back="route('admin.ads.index')" />
    @include('admin._nav')

    <form method="POST" action="{{ route('admin.ads.update', $ad) }}" enctype="multipart/form-data" class="card mx-auto max-w-xl space-y-4 p-5">
        @csrf @method('PUT')
        @include('admin.ads._form')
        <p class="tabular rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ number_format($ad->impressions, 0, ',', '.') }} tayang · {{ number_format($ad->clicks, 0, ',', '.') }} klik · CTR {{ number_format($ad->ctr, 1, ',', '') }}%</p>
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.ads.index') }}" class="btn-ghost">Batal</a>
            <button class="btn-primary">Simpan</button>
        </div>
    </form>
</x-app-layout>
