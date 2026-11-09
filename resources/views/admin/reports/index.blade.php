<x-app-layout>
    <x-slot:title>Laporan</x-slot:title>

    <x-page-header title="Laporan pengguna" subtitle="Tinjau laporan barang dan lapak: sembunyikan barang, nonaktifkan akun, atau abaikan." />
    @include('admin._nav')

    <nav class="mb-5 flex gap-2" aria-label="Status laporan">
        @foreach (\App\Enums\ReportStatus::cases() as $option)
            <a href="{{ route('admin.reports.index', ['status' => $option->value]) }}" class="{{ $status === $option ? 'chip-active' : 'chip' }}">{{ $option->label() }} <span class="opacity-60">{{ $counts[$option->value] ?? 0 }}</span></a>
        @endforeach
    </nav>

    @error('report')<p class="mb-4 rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror

    @if ($reports->isEmpty())
        <x-empty-state icon="check" title="Tidak ada laporan" :description="$status === \App\Enums\ReportStatus::Open ? 'Semua laporan sudah ditangani.' : 'Belum ada laporan dengan status ini.'" />
    @else
        <div class="space-y-3">
            @foreach ($reports as $report)
                @php
                    $target = $report->reportable;
                    $isProduct = $target instanceof \App\Models\Product;
                @endphp
                <article class="card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start gap-4">
                        @if ($isProduct)
                            <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                @if ($target->cover_url)<img src="{{ $target->cover_url }}" alt="" class="size-full object-cover">@endif
                            </div>
                        @elseif ($target)
                            <x-avatar :user="$target" size="size-16" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700">{{ $report->reason->label() }}</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $isProduct ? 'Barang' : 'Lapak' }}</span>
                            </p>
                            @if ($target)
                                <a href="{{ $isProduct ? route('products.show', $target) : route('sellers.show', $target) }}" target="_blank" class="mt-1 block truncate font-bold text-ink hover:text-brand-600">{{ $isProduct ? $target->name : $target->name }}</a>
                                <p class="text-xs text-slate-500">
                                    @if ($isProduct)Penjual {{ $target->seller->name }} · <x-status-badge :status="$target->status" />@else{{ $target->email }}@endif
                                    @if (! ($isProduct ? $target->seller->is_active : $target->is_active)) · <span class="font-semibold text-red-600">akun nonaktif</span>@endif
                                </p>
                            @else
                                <p class="mt-1 text-sm text-slate-500">Target laporan sudah dihapus.</p>
                            @endif
                            @if ($report->note)
                                <p class="mt-2 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">“{{ $report->note }}”</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-400">Dilaporkan {{ $report->reporter->name }} · {{ $report->created_at->diffForHumans() }}</p>
                            @if ($report->handled_at)
                                <p class="mt-1 text-xs text-slate-500">{{ $report->status->label() }} oleh {{ $report->handler?->name ?? 'admin' }} {{ $report->handled_at->diffForHumans() }}{{ $report->admin_note ? ': '.$report->admin_note : '' }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($status === \App\Enums\ReportStatus::Open && $target)
                        <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center">
                            @csrf @method('PATCH')
                            <input name="admin_note" maxlength="500" class="field flex-1" placeholder="Catatan admin (opsional, dikirim ke penjual bila barang disembunyikan)" aria-label="Catatan admin">
                            <div class="flex flex-wrap gap-2">
                                @if ($isProduct)
                                    <button name="action" value="hide_product" class="btn-danger px-3.5 py-2"><x-icon name="eye" class="size-4" /> Sembunyikan barang</button>
                                @endif
                                <button name="action" value="deactivate_user" class="btn-ghost px-3.5 py-2 text-red-600" onclick="return confirm('Nonaktifkan akun ini? Pengguna akan dikeluarkan dan tidak bisa masuk.')"><x-icon name="ban" class="size-4" /> Nonaktifkan akun</button>
                                <button name="action" value="dismiss" class="btn-ghost px-3.5 py-2">Abaikan</button>
                            </div>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $reports->links() }}</div>
    @endif
</x-app-layout>
