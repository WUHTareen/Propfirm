@php
    $badge = fn (string $s) => match ($s) {
        'paid', 'processing', 'completed' => 'bg-brand-500/15 text-brand-300',
        'pending' => 'bg-amber-500/15 text-amber-300',
        'cancelled', 'expired', 'refunded' => 'bg-red-500/15 text-red-300',
        default => 'bg-ink-700 text-slate-400',
    };
@endphp

<x-dashboard title="Orders">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Orders</h1>
        <p class="mt-1 text-sm text-slate-400">Your challenge purchases and their payment status.</p>
    </x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-dashed border-ink-600 bg-ink-800/60 p-10 text-center">
            <h2 class="font-display text-lg font-semibold text-white">No orders yet</h2>
            <p class="mx-auto mt-1 mb-5 max-w-md text-sm text-slate-400">You haven't bought a challenge yet.</p>
            <a href="{{ route('dashboard.buynow') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 font-semibold text-ink-950 transition hover:bg-brand-400">Buy a challenge</a>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-ink-600 bg-ink-800">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-ink-700 text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Plan</th>
                        <th class="px-4 py-3 font-medium">Platform</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-slate-200">{{ $order->plan_snapshot['name'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ strtoupper($order->platform) }}</td>
                            <td class="px-4 py-3 tabular-nums text-slate-200">${{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge($order->status) }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $order->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($order->status === 'pending')
                                    <a href="{{ route('dashboard.orders.pay', $order) }}" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-ink-950 transition hover:bg-brand-400">Pay</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-dashboard>
