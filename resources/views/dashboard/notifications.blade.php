<x-dashboard title="Notifications">
    <x-slot:header>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-bold text-white">Notifications</h1>
                <p class="mt-1 text-sm text-slate-400">Updates about your accounts, orders and rewards.</p>
            </div>
            @if ($notifications->total() > 0 && auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('dashboard.notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="shrink-0 rounded-lg border border-ink-600 px-3.5 py-2 text-sm text-slate-300 transition hover:bg-ink-700 hover:text-white">Mark all read</button>
                </form>
            @endif
        </div>
    </x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">{{ session('status') }}</div>
    @endif

    @if ($notifications->isEmpty())
        <div class="rounded-2xl border border-dashed border-ink-600 bg-ink-800/60 p-10 text-center">
            <h2 class="font-display text-lg font-semibold text-white">You're all caught up</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">You have no notifications yet.</p>
        </div>
    @else
        <div class="divide-y divide-ink-700 overflow-hidden rounded-2xl border border-ink-600 bg-ink-800">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $title = $data['title'] ?? 'Notification';
                    $message = $data['message'] ?? ($data['body'] ?? '');
                    $url = $data['url'] ?? ($data['action_url'] ?? null);
                    $unread = is_null($notification->read_at);
                @endphp
                <div class="flex items-start gap-3 px-4 py-4 {{ $unread ? 'bg-brand-500/[0.04]' : '' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $unread ? 'bg-brand-400' : 'bg-ink-600' }}"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-slate-100">{{ $title }}</p>
                            @if ($unread)
                                <span class="rounded-full bg-brand-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-300">New</span>
                            @endif
                        </div>
                        @if ($message)
                            <p class="mt-0.5 text-sm text-slate-400">{{ $message }}</p>
                        @endif
                        <div class="mt-2 flex items-center gap-3 text-xs">
                            <span class="text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                            @if ($url)
                                <a href="{{ $url }}" class="text-brand-300 hover:text-brand-200">View</a>
                            @endif
                            @if ($unread)
                                <form method="POST" action="{{ route('dashboard.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="text-slate-400 hover:text-white">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</x-dashboard>
