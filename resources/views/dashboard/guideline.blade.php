@php
    $pct = fn ($n) => $n === null ? '—' : rtrim(rtrim((string) $n, '0'), '.').'%';
    $firstType = array_key_first($types);
@endphp

<x-dashboard title="Guideline">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Guideline</h1>
        <p class="mt-1 text-sm text-slate-400">The rules your account is measured against. Breaking one ends the evaluation.</p>
    </x-slot:header>

    @if (empty($types))
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <p class="text-sm text-slate-500">No challenge plans are active yet, so there are no rules to show.</p>
        </div>
    @else
        <div x-data="{ type: '{{ $firstType }}', tab: 'conditions' }">
            {{-- Challenge-type tabs --}}
            <div class="inline-flex flex-wrap rounded-xl border border-ink-700 bg-ink-900 p-1">
                @foreach ($types as $key => $label)
                    <button type="button" x-on:click="type = '{{ $key }}'"
                            :class="type === '{{ $key }}' ? 'bg-brand-500 text-ink-950' : 'text-slate-300 hover:text-white'"
                            class="rounded-lg px-5 py-2 text-sm font-semibold transition">{{ $label }}</button>
                @endforeach
            </div>

            {{-- Sub-tabs --}}
            <div class="mt-5 flex gap-1 border-b border-ink-700">
                @foreach (['conditions' => 'Trading Conditions', 'prohibited' => 'Prohibited', 'allowed' => 'Allowed'] as $t => $tl)
                    <button type="button" x-on:click="tab = '{{ $t }}'"
                            :class="tab === '{{ $t }}' ? 'border-brand-400 text-brand-300' : 'border-transparent text-slate-400 hover:text-white'"
                            class="border-b-2 px-4 py-2.5 text-sm font-medium transition">{{ $tl }}</button>
                @endforeach
            </div>

            {{-- Trading conditions (per challenge type) --}}
            <div x-show="tab === 'conditions'" x-cloak class="mt-5">
                @foreach ($plansByType as $key => $plan)
                    <div x-show="type === '{{ $key }}'" x-cloak>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @php
                                $rows = [
                                    'Phase 1 profit target' => $pct($plan->phase1_target_percent),
                                    'Phase 2 profit target' => $plan->phase2_target_percent ? $pct($plan->phase2_target_percent) : 'N/A',
                                    'Daily loss limit' => $pct($plan->daily_drawdown_percent),
                                    'Max loss limit' => $pct($plan->max_drawdown_percent),
                                    'Leverage' => '1:'.$plan->leverage,
                                    'Min trading days' => (string) $plan->min_trading_days,
                                    'Drawdown type' => ucfirst($plan->drawdown_type),
                                    'Profit split' => $pct($plan->profit_split_percent),
                                    'Consistency rule' => $plan->has_consistency_rule ? $pct($plan->consistency_percent) : 'None',
                                ];
                            @endphp
                            @foreach ($rows as $label => $value)
                                <div class="flex items-center justify-between rounded-xl border border-ink-700 bg-ink-800 px-4 py-3">
                                    <span class="text-sm text-slate-400">{{ $label }}</span>
                                    <span class="font-mono text-sm font-semibold text-white">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Prohibited --}}
            <div x-show="tab === 'prohibited'" x-cloak class="mt-5 space-y-3">
                @forelse ($prohibited as $rule)
                    <details class="group rounded-xl border border-ink-700 bg-ink-800 px-5 py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-100">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                {{ $rule['title'] }}
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-slate-500 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </summary>
                        <p class="mt-2 pl-6 text-sm text-slate-400">{{ $rule['body'] }}</p>
                    </details>
                @empty
                    <p class="text-sm text-slate-500">No prohibited activities configured.</p>
                @endforelse
            </div>

            {{-- Allowed --}}
            <div x-show="tab === 'allowed'" x-cloak class="mt-5 space-y-3">
                @forelse ($allowed as $rule)
                    <div class="rounded-xl border border-ink-700 bg-ink-800 px-5 py-4">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-100">
                            <svg class="h-4 w-4 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ $rule['title'] }}
                        </h3>
                        <p class="mt-1.5 pl-6 text-sm text-slate-400">{{ $rule['body'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No allowed activities configured.</p>
                @endforelse
            </div>
        </div>
    @endif
</x-dashboard>
