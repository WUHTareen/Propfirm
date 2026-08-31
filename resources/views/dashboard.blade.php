@php
    $accounts = auth()->user()->tradingAccounts()->latest()->get();
@endphp

<x-dashboard title="Overview">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-slate-400">Your account overview.</p>
    </x-slot:header>

    @if ($accounts->isEmpty())
        <div class="rounded-2xl border border-dashed border-ink-600 bg-ink-800/60 p-10 text-center">
            <div class="mx-auto mb-4 grid h-12 w-12 place-items-center rounded-xl bg-ink-700 text-brand-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 12 4l9 9.5M4.5 12v7.5h15V12" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-semibold text-white">No accounts yet</h2>
            <p class="mx-auto mt-1 mb-5 max-w-md text-sm text-slate-400">
                You haven't purchased a challenge yet. Buy one to receive your MT5/MT4 login and start your evaluation.
            </p>
            <a href="{{ route('dashboard.buynow') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 font-semibold text-ink-950 transition hover:bg-brand-400">
                Buy a challenge
            </a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($accounts as $account)
                @php
                    $statusColor = match ($account->status) {
                        'active' => 'bg-brand-500/15 text-brand-300',
                        'passed', 'funded' => 'bg-emerald-500/15 text-emerald-300',
                        'breached', 'disabled' => 'bg-red-500/15 text-red-300',
                        default => 'bg-amber-500/15 text-amber-300',
                    };
                @endphp
                <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-display text-lg font-semibold text-white">${{ number_format($account->account_size) }}</p>
                            <p class="text-sm text-slate-400">{{ strtoupper($account->platform) }} · {{ str_replace('_', '-', ucfirst($account->challenge_type)) }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusColor }}">{{ str_replace('_', ' ', ucfirst($account->status)) }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Phase</dt>
                            <dd class="text-slate-200">{{ $account->current_phase }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Login</dt>
                            <dd class="font-mono text-slate-200">{{ $account->login ?? '— pending —' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Profit target</dt>
                            <dd class="tabular-nums text-slate-200">{{ $account->profit_target_amount ? '$'.number_format($account->profit_target_amount) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Max loss</dt>
                            <dd class="tabular-nums text-slate-200">{{ $account->max_drawdown_limit ? '$'.number_format($account->max_drawdown_limit) : '—' }}</dd>
                        </div>
                    </dl>

                    @if ($account->status === 'pending_assignment')
                        <p class="mt-4 rounded-lg bg-ink-900 px-3 py-2 text-xs text-slate-400">Your credentials are being prepared. You'll be notified when your account is ready.</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-dashboard>
