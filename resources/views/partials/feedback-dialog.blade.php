{{-- Beri Masukan (F16): dibuka dari footer, menu Akun, atau setelah booking Selesai lewat event "open-feedback". --}}
@php($feedbackErrors = $errors->getBag('feedback'))
<div x-data="{ open: {{ $feedbackErrors->any() ? 'true' : 'false' }}, score: {{ (int) old('score', 0) }} }" @open-feedback.window="open = true">
    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="masukan-judul" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="open = false"></div>
        <form x-show="open" method="POST" action="{{ route('feedback.store') }}" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-4" x-transition:enter-end="translate-y-0 opacity-100"
              class="relative w-full space-y-4 rounded-t-3xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-float sm:max-w-md sm:rounded-3xl sm:p-6">
            @csrf
            <input type="hidden" name="page" value="{{ request()->path() }}">
            <div class="mx-auto h-1.5 w-10 rounded-full bg-slate-200 sm:hidden"></div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="masukan-judul" class="text-lg font-extrabold">Beri masukan</h2>
                    <p class="text-sm text-slate-500">Seberapa mudah Jualin Aja ITK dipakai?</p>
                </div>
                <button type="button" @click="open = false" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-ink" aria-label="Tutup"><x-icon name="x" /></button>
            </div>

            <fieldset>
                <legend class="sr-only">Skor 1 sampai 5</legend>
                <div class="grid grid-cols-5 gap-2">
                    @foreach ([1 => 'Sulit', 2 => '', 3 => 'Biasa', 4 => '', 5 => 'Mudah'] as $value => $hint)
                        <label class="cursor-pointer text-center">
                            <input type="radio" name="score" value="{{ $value }}" x-model.number="score" class="peer sr-only" required>
                            <span class="tabular flex h-12 items-center justify-center rounded-xl border border-slate-200 text-lg font-extrabold text-slate-500 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">{{ $value }}</span>
                            <span class="mt-1 block h-4 text-[11px] text-slate-400">{{ $hint }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($feedbackErrors->has('score'))<p class="mt-1 text-sm text-red-600">{{ $feedbackErrors->first('score') }}</p>@endif
            </fieldset>

            <div>
                <label for="feedback-comment" class="field-label">Komentar <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea id="feedback-comment" name="comment" rows="3" maxlength="1000" class="field" placeholder="Apa yang membingungkan atau perlu ditambah?">{{ old('comment') }}</textarea>
                @if ($feedbackErrors->has('comment'))<p class="mt-1 text-sm text-red-600">{{ $feedbackErrors->first('comment') }}</p>@endif
            </div>

            <button class="btn-primary w-full" :disabled="score === 0">Kirim masukan</button>
        </form>
    </div>
</div>
