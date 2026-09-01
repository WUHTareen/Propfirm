@php
    $money = fn ($n) => $n >= 1000 && $n % 1000 === 0 ? '$'.($n / 1000).'K' : '$'.number_format($n);
    $pct = fn ($n) => $n === null ? null : rtrim(rtrim((string) $n, '0'), '.').'%';
    $firstType = array_key_first($types);
@endphp

<x-marketing title="Pricing" description="Challenge pricing and evaluation rules for every account size.">
    <section class="mx-auto max-w-6xl px-6 py-16">
        <div class="text-center">
            <h1 class="font-display text-4xl font-extrabold text-white">Challenge pricing</h1>
            <p class="mx-auto mt-3 max-w-xl text-slate-400">Pick a challenge type and account size. One-time fee, all prices in USD. The client sets every number in the admin panel.</p>
        </div>

        <div x-data="{ type: '{{ $firstType }}' }" class="mt-10">
            {{-- Type toggle --}}
            <div class="flex justify-center">
                <div class="inline-flex flex-wrap justify-center rounded-xl border border-ink-700 bg-ink-900 p-1">
                    @foreach ($types as $key => $label)
                        <button type="button" x-on:click="type = '{{ $key }}'"
                                :class="type === '{{ $key }}' ? 'bg-brand-500 text-ink-950' : 'text-slate-300 hover:text-white'"
                                class="rounded-lg px-5 py-2 text-sm font-semibold transition">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($types as $key => $label)
                <div x-show="type === '{{ $key }}'" x-cloak class="mt-8">
                    {{-- Cards (mobile + desktop) --}}
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach (($plans['byType'][$key] ?? []) as $size => $plan)
                            <div class="flex flex-col rounded-2xl border border-ink-700 bg-ink-900 p-5">
                                <p class="font-display text-2xl font-bold text-white">{{ $money($size) }}</p>
                                <p class="text-xs text-slate-500">account size</p>
                                <p class="mt-4 font-display text-2xl font-bold text-brand-300">${{ number_format($plan->price, 0) }}</p>
                                <p class="text-xs text-slate-500">one-time fee</p>
                                <ul class="mt-4 flex-1 space-y-2 border-t border-ink-800 pt-4 text-sm text-slate-400">
                                    <li class="flex justify-between"><span>Profit target</span><span class="text-slate-200">{{ $pct($plan->phase1_target_percent) ?? '—' }}@if($plan->phase2_target_percent) / {{ $pct($plan->phase2_target_percent) }}@endif</span></li>
                                    <li class="flex justify-between"><span>Daily loss</span><span class="text-slate-200">{{ $pct($plan->daily_drawdown_percent) ?? '—' }}</span></li>
                                    <li class="flex justify-between"><span>Max loss</span><span class="text-slate-200">{{ $pct($plan->max_drawdown_percent) ?? '—' }}</span></li>
                                    <li class="flex justify-between"><span>Leverage</span><span class="text-slate-200">1:{{ $plan->leverage }}</span></li>
                                    <li class="flex justify-between"><span>Min trading days</span><span class="text-slate-200">{{ $plan->min_trading_days }}</span></li>
                                    <li class="flex justify-between"><span>Profit split</span><span class="text-slate-200">{{ $pct($plan->profit_split_percent) ?? '—' }}</span></li>
                                </ul>
                                <a href="{{ route('register') }}" class="mt-5 rounded-lg bg-brand-500 px-4 py-2.5 text-center text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Get funded</a>
                            </div>
                        @endforeach
                    </div>

                    @if (empty($plans['byType'][$key]))
                        <p class="mt-6 text-center text-sm text-slate-500">No plans configured for this challenge type yet.</p>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="mx-auto mt-10 max-w-2xl text-center text-xs text-slate-600">
            All accounts are evaluated on MetaTrader 5 & 4. Prices and rules are set by the firm and may change. See the
            <a href="{{ route('rules') }}" class="text-brand-400 hover:text-brand-300">trading rules</a> for full details.
        </p>
    </section>
</x-marketing>
