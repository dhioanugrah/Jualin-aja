<x-app-layout>
    <x-slot:title>Notifikasi</x-slot:title>

    <div class="mx-auto max-w-2xl">
        <x-page-header title="Notifikasi">
            @if ($hasUnread)
                <x-slot:actions>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="btn-ghost px-4 py-2"><x-icon name="check" class="size-4" /> Tandai semua dibaca</button>
                    </form>
                </x-slot:actions>
            @endif
        </x-page-header>

        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" title="Belum ada notifikasi" description="Hasil moderasi, penawaran, dan booking akan muncul di sini." />
        @else
            <ul class="card divide-y divide-slate-100 overflow-hidden">
                @foreach ($notifications as $notification)
                    @php($tone = $notification->data['tone'] ?? 'info')
                    <li>
                        <a href="{{ $notification->data['url'] ?? '#' }}" @class(['flex gap-4 px-5 py-4 transition hover:bg-slate-50', 'bg-brand-50/50' => $notification->unread()])>
                            <span @class([
                                'flex size-10 shrink-0 items-center justify-center rounded-full',
                                'bg-emerald-50 text-emerald-600' => $tone === 'success',
                                'bg-amber-50 text-amber-600' => $tone === 'warning',
                                'bg-red-50 text-red-600' => $tone === 'danger',
                                'bg-brand-50 text-brand-600' => $tone === 'info',
                            ])>
                                <x-icon :name="match ($tone) { 'success' => 'check', 'warning', 'danger' => 'alert', default => 'bell' }" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-ink">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                                <p class="text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @if ($notification->unread())
                                <span class="mt-2 size-2 shrink-0 rounded-full bg-brand-600"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
