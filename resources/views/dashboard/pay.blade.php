@php
    $methodLabel = config("payments.methods.{$order->payment_method}.label", strtoupper($order->payment_method));
@endphp

<x-dashboard title="Pay order">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Complete your payment</h1>
        <p class="mt-1 text-sm text-slate-400">Order <span class="font-mono">{{ $order->order_number }}</span></p>
    </x-slot:header>

    <div class="mx-auto max-w-lg rounded-2xl border border-ink-600 bg-ink-800 p-6">
        <div class="flex items-baseline justify-between border-b border-ink-700 pb-4">
            <span class="text-slate-400">Amount due</span>
            <span class="font-display text-2xl font-bold text-white">${{ number_format($order->total, 2) }}</span>
        </div>
        <div class="flex items-center justify-between border-b border-ink-700 py-4 text-sm">
            <span class="text-slate-400">Pay with</span>
            <span class="text-slate-200">{{ $methodLabel }}</span>
        </div>

        @if ($intent->payAddress)
            <div class="py-5">
                <p class="mb-2 text-sm text-slate-400">Send exactly <b class="text-slate-200">${{ number_format($order->total, 2) }}</b> worth of {{ $methodLabel }} to:</p>
                <div class="flex items-center gap-2 rounded-lg border border-ink-600 bg-ink-900 p-3">
                    <code class="flex-1 break-all font-mono text-sm text-brand-300">{{ $intent->payAddress }}</code>
                </div>
            </div>
        @else
            <div class="py-5">
                <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-amber-200">
                    Payment wallet isn't configured yet. Your order is recorded as <b>pending</b> — our team will send you payment instructions and confirm receipt manually.
                </div>
            </div>
        @endif

        <div class="rounded-lg bg-ink-900 p-4 text-sm text-slate-400">
            After your transfer, the order stays <b class="text-amber-300">pending</b> until payment is confirmed. You'll be notified and your account will be prepared for assignment.
        </div>

        <div class="mt-5 flex gap-3">
            <a href="{{ route('dashboard.orders') }}" class="flex-1 rounded-lg border border-ink-600 px-4 py-2.5 text-center text-sm text-slate-200 transition hover:bg-ink-700">Back to orders</a>
        </div>
    </div>
</x-dashboard>
