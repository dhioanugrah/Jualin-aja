<x-app-layout>
    <x-slot:title>Panel Admin</x-slot:title>

    <x-page-header title="Panel Admin" subtitle="Ringkasan aktivitas Jualin Aja ITK." />
    @include('admin._nav')

    @if ($openReports + $openDisputes > 0)
        <div class="mb-6 grid gap-3 sm:grid-cols-2">
            @if ($openReports > 0)
                <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 hover:bg-red-100/70">
                    <x-icon name="flag" /> <span class="flex-1"><strong>{{ $openReports }}</strong> laporan menunggu ditinjau</span> <x-icon name="chevron-right" class="size-4" />
                </a>
            @endif
            @if ($openDisputes > 0)
                <a href="{{ route('admin.disputes.index') }}" class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 hover:bg-amber-100/70">
                    <x-icon name="scale" /> <span class="flex-1"><strong>{{ $openDisputes }}</strong> sengketa menunggu keputusan</span> <x-icon name="chevron-right" class="size-4" />
                </a>
            @endif
        </div>
    @endif

    <dl class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ([
            ['icon' => 'users', 'label' => 'Mahasiswa', 'value' => number_format($userCount, 0, ',', '.')],
            ['icon' => 'flag', 'label' => 'Pelanggaran aktif', 'value' => $activeViolations],
            ['icon' => 'megaphone', 'label' => 'Iklan tayang · klik', 'value' => number_format($ads['impressions'], 0, ',', '.').' · '.number_format($ads['clicks'], 0, ',', '.')],
            ['icon' => 'smile', 'label' => 'Rata-rata masukan', 'value' => $feedback['count'] > 0 ? number_format($feedback['average'], 1, ',', '').' / 5' : '–'],
        ] as $stat)
            <div class="card p-5">
                <dt class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><x-icon :name="$stat['icon']" class="size-4" /> {{ $stat['label'] }}</dt>
                <dd class="tabular mt-2 text-2xl font-extrabold text-ink sm:text-3xl">{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <h2 class="flex items-center gap-2 font-extrabold"><x-icon name="package" class="size-4 text-brand-600" /> Barang per status</h2>
            <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach (\App\Enums\ProductStatus::cases() as $status)
                    <div @class(['rounded-xl bg-slate-50 p-3', 'ring-2 ring-amber-300' => $status === \App\Enums\ProductStatus::Pending && $productCounts[$status->value] > 0])>
                        <dt><x-status-badge :status="$status" /></dt>
                        <dd class="tabular mt-1.5 text-2xl font-extrabold text-ink">{{ $productCounts[$status->value] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-5">
            <h2 class="flex items-center gap-2 font-extrabold"><x-icon name="calendar" class="size-4 text-brand-600" /> Booking per status</h2>
            <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach (\App\Enums\BookingStatus::cases() as $status)
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt><x-status-badge :status="$status" /></dt>
                        <dd class="tabular mt-1.5 text-2xl font-extrabold text-ink">{{ $bookingCounts[$status->value] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between">
            <h2 class="text-lg font-extrabold">Antrean moderasi terlama</h2>
            <a href="{{ route('admin.moderation.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Buka antrean</a>
        </div>
        @if ($queue->isEmpty())
            <x-empty-state icon="check" title="Antrean kosong" description="Semua barang sudah dimoderasi." />
        @else
            <ul class="card divide-y divide-slate-100 overflow-hidden">
                @foreach ($queue as $product)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                            @if ($product->cover_url)<img src="{{ $product->cover_url }}" alt="" class="size-full object-cover">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">{{ $product->seller->name }} · masuk {{ $product->updated_at->diffForHumans() }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-layout>
