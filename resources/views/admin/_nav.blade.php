<nav class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" aria-label="Menu admin">
    @foreach ([
        ['route' => 'admin.dashboard', 'label' => 'Ringkasan', 'icon' => 'layout'],
        ['route' => 'admin.moderation.index', 'label' => 'Moderasi', 'icon' => 'shield-check'],
        ['route' => 'admin.reports.index', 'label' => 'Laporan', 'icon' => 'flag'],
        ['route' => 'admin.disputes.index', 'label' => 'Sengketa', 'icon' => 'scale'],
        ['route' => 'admin.ads.index', 'label' => 'Iklan', 'icon' => 'megaphone'],
        ['route' => 'admin.cod-locations.index', 'label' => 'Titik COD', 'icon' => 'map-pin'],
        ['route' => 'admin.feedback.index', 'label' => 'Masukan', 'icon' => 'smile'],
    ] as $item)
        <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'chip-active' : 'chip' }} shrink-0">
            <x-icon :name="$item['icon']" class="size-4" /> {{ $item['label'] }}
        </a>
    @endforeach
</nav>
