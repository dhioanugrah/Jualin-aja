{{-- Tombol Laporkan (F13) untuk barang atau lapak. Variabel: $type ('barang'|'lapak'), $id, $label --}}
<div x-data="{ open: {{ $errors->hasAny(['reason', 'note', 'report']) ? 'true' : 'false' }}, reason: @js(old('reason', '')) }" class="{{ $wrapperClass ?? '' }}">
    <button type="button" @click="open = true" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-red-600"><x-icon name="flag" class="size-3.5" /> Laporkan {{ $type }}</button>

    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="lapor-judul" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="open = false"></div>
        <form x-show="open" method="POST" action="{{ route('reports.store') }}" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-4" x-transition:enter-end="translate-y-0 opacity-100"
              class="relative max-h-[90dvh] w-full space-y-4 overflow-y-auto rounded-t-3xl bg-white p-5 shadow-float sm:max-w-md sm:rounded-3xl sm:p-6">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="id" value="{{ $id }}">
            <div class="mx-auto h-1.5 w-10 rounded-full bg-slate-200 sm:hidden"></div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="lapor-judul" class="text-lg font-extrabold">Laporkan {{ $type }}</h2>
                    <p class="text-sm text-slate-500">{{ $label }}. Laporan hanya terlihat oleh admin.</p>
                </div>
                <button type="button" @click="open = false" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-ink" aria-label="Tutup"><x-icon name="x" /></button>
            </div>

            <fieldset class="grid gap-2">
                <legend class="field-label">Alasan</legend>
                @foreach (\App\Enums\ReportReason::cases() as $reason)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm has-[:checked]:border-red-300 has-[:checked]:bg-red-50/60">
                        <input type="radio" name="reason" value="{{ $reason->value }}" x-model="reason" required class="text-red-600 focus:ring-red-500">
                        <span class="font-semibold text-ink">{{ $reason->label() }}</span>
                    </label>
                @endforeach
                <x-field-error name="reason" />
            </fieldset>

            <div>
                <label for="report-note-{{ $type }}" class="field-label">Keterangan <span class="font-normal text-slate-400" x-show="reason !== 'other'">(opsional)</span></label>
                <textarea id="report-note-{{ $type }}" name="note" rows="3" maxlength="500" :required="reason === 'other'" class="field" placeholder="Ceritakan singkat apa yang terjadi.">{{ old('note') }}</textarea>
                <x-field-error name="note" />
                <x-field-error name="report" />
            </div>

            <button class="btn-danger w-full">Kirim laporan</button>
        </form>
    </div>
</div>
