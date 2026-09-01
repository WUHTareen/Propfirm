@php
    $badge = fn (string $s) => match ($s) {
        'paid' => 'bg-brand-500/15 text-brand-300',
        'approved', 'processing' => 'bg-sky-500/15 text-sky-300',
        'rejected' => 'bg-red-500/15 text-red-300',
        default => 'bg-amber-500/15 text-amber-300',
    };
    $eligibleAccounts = $accounts->filter(fn ($a) => $service->isEligible($a));
    $methods = config('payments.methods');
@endphp

<x-dashboard title="Withdrawals">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Withdrawals</h1>
        <p class="mt-1 text-sm text-slate-400">Request payouts from your funded accounts.</p>
    </x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Eligibility --}}
    <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">Account withdrawal status</h2>
        @if ($accounts->isEmpty())
            <p class="mt-3 text-sm text-slate-500">You have no funded accounts yet. Pass an evaluation to unlock payouts.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-ink-700 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2 font-medium">Account</th>
                            <th class="px-3 py-2 font-medium">Size</th>
                            <th class="px-3 py-2 font-medium">KYC</th>
                            <th class="px-3 py-2 font-medium">Trading days</th>
                            <th class="px-3 py-2 font-medium">Available</th>
                            <th class="px-3 py-2 font-medium">Eligible</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach ($accounts as $a)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-300">#{{ $a->id }} · {{ strtoupper($a->platform) }}</td>
                                <td class="px-3 py-2 tabular-nums text-slate-200">${{ number_format($a->account_size) }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $a->user->kyc_status === 'approved' ? 'bg-brand-500/15 text-brand-300' : 'bg-amber-500/15 text-amber-300' }}">{{ ucfirst($a->user->kyc_status) }}</span>
                                </td>
                                <td class="px-3 py-2 tabular-nums text-slate-300">{{ $a->trading_days_count }}</td>
                                <td class="px-3 py-2 tabular-nums text-slate-200">${{ number_format($service->availableProfit($a), 2) }}</td>
                                <td class="px-3 py-2">
                                    @if ($service->isEligible($a))
                                        <span class="text-brand-400">Yes</span>
                                    @else
                                        <span class="text-slate-500">No</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Request form --}}
    @if ($eligibleAccounts->isNotEmpty())
        <section class="mt-6 rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <h2 class="font-display text-base font-semibold text-white">Request a payout</h2>
            <form method="POST" action="{{ route('dashboard.withdrawal.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="trading_account_id" class="field-label">Account</label>
                    <select id="trading_account_id" name="trading_account_id" class="field-input" required>
                        @foreach ($eligibleAccounts as $a)
                            <option value="{{ $a->id }}">#{{ $a->id }} — ${{ number_format($a->account_size) }} (available ${{ number_format($service->availableProfit($a), 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="amount" class="field-label">Amount (USD)</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="1" class="field-input" placeholder="0.00" required>
                </div>
                <div>
                    <label for="method" class="field-label">Method</label>
                    <select id="method" name="method" class="field-input" required>
                        @foreach ($methods as $value => $m)
                            <option value="{{ $value }}">{{ $m['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="wallet_address" class="field-label">Wallet address</label>
                    <input id="wallet_address" name="wallet_address" type="text" class="field-input" placeholder="Your receiving wallet" required>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary sm:w-auto sm:px-6">Request withdrawal</button>
                </div>
            </form>
        </section>
    @endif

    {{-- History --}}
    <section class="mt-6 rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">Withdrawal history</h2>
        @if ($withdrawals->isEmpty())
            <p class="mt-3 text-sm text-slate-500">No withdrawals yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-ink-700 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2 font-medium">ID</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                            <th class="px-3 py-2 font-medium">Amount</th>
                            <th class="px-3 py-2 font-medium">Method</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach ($withdrawals as $w)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-300">{{ $w->withdrawal_number }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $w->created_at->format('d M Y') }}</td>
                                <td class="px-3 py-2 tabular-nums text-slate-200">${{ number_format($w->amount, 2) }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $methods[$w->method]['label'] ?? strtoupper($w->method) }}</td>
                                <td class="px-3 py-2"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge($w->status) }}">{{ ucfirst($w->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-dashboard>
