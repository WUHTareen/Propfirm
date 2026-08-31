<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-white">Buy a challenge</h1>
        <p class="mt-1 text-sm text-slate-400">Pick your evaluation, then pay with crypto.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr,20rem] lg:items-start">
        {{-- Options --}}
        <div class="space-y-6">
            {{-- Challenge type --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Challenge type</h2>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['two_step' => '2-Step', 'one_step' => '1-Step', 'instant' => 'Instant'] as $value => $label)
                        <button type="button" wire:click="$set('challengeType', '{{ $value }}')"
                                class="rounded-lg border px-3 py-2.5 text-sm font-medium transition
                                       {{ $challengeType === $value ? 'border-brand-500 bg-brand-500/10 text-brand-300' : 'border-ink-600 text-slate-300 hover:border-ink-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Account size --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Account size</h2>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @forelse ($sizes as $size)
                        <button type="button" wire:click="$set('accountSize', {{ $size }})"
                                class="rounded-lg border px-3 py-2.5 text-sm font-semibold transition
                                       {{ (float) $accountSize === (float) $size ? 'border-brand-500 bg-brand-500/10 text-brand-300' : 'border-ink-600 text-slate-200 hover:border-ink-500' }}">
                            ${{ number_format($size) }}
                        </button>
                    @empty
                        <p class="col-span-full text-sm text-slate-500">No plans available for this type yet.</p>
                    @endforelse
                </div>
                @error('accountSize') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </section>

            {{-- Platform --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Platform</h2>
                <div class="grid grid-cols-2 gap-2 sm:max-w-xs">
                    @foreach (['mt5' => 'MetaTrader 5', 'mt4' => 'MetaTrader 4'] as $value => $label)
                        <button type="button" wire:click="$set('platform', '{{ $value }}')"
                                class="rounded-lg border px-3 py-2.5 text-sm font-medium transition
                                       {{ $platform === $value ? 'border-brand-500 bg-brand-500/10 text-brand-300' : 'border-ink-600 text-slate-300 hover:border-ink-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Payment method --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Payment method</h2>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($methods as $value => $m)
                        <button type="button" wire:click="$set('method', '{{ $value }}')"
                                class="flex items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition
                                       {{ $method === $value ? 'border-brand-500 bg-brand-500/10 text-brand-300' : 'border-ink-600 text-slate-300 hover:border-ink-500' }}">
                            <span>{{ $m['label'] }}</span>
                            @if ($m['note'])
                                <span class="rounded bg-brand-500/20 px-1.5 py-0.5 font-mono text-[10px] uppercase text-brand-300">{{ $m['note'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Discounts --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Discounts &amp; points</h2>
                <div>
                    <label class="field-label">Coupon code</label>
                    <input type="text" wire:model.live.debounce.500ms="couponCode" class="field-input" placeholder="Enter code">
                    @if ($couponStatus === 'valid')
                        <p class="mt-1.5 text-sm text-brand-400">Coupon applied.</p>
                    @elseif ($couponStatus === 'invalid')
                        <p class="mt-1.5 text-sm text-red-400">This coupon is not valid for this order.</p>
                    @endif
                </div>

                @if ($pointsBalance > 0)
                    <label class="flex items-center gap-2.5 text-sm text-slate-300">
                        <input type="checkbox" wire:model.live="redeemPoints" class="rounded border-ink-600 bg-ink-900 text-brand-500 focus:ring-brand-500">
                        Use my points ({{ number_format($pointsBalance) }} pts = ${{ number_format($pointsBalance / 100, 2) }})
                    </label>
                @endif

                <label class="flex items-center gap-2.5 text-sm text-slate-300">
                    <input type="checkbox" wire:model.live="cashbackOptIn" class="rounded border-ink-600 bg-ink-900 text-brand-500 focus:ring-brand-500">
                    Earn cashback points on this purchase (10 pts per $1)
                </label>
            </section>
        </div>

        {{-- Summary --}}
        <aside class="rounded-2xl border border-ink-600 bg-ink-800 p-5 lg:sticky lg:top-8">
            <h2 class="font-display text-lg font-semibold text-white">Order summary</h2>

            @if ($plan)
                <p class="mt-1 text-sm text-slate-400">{{ $plan->name }} · {{ strtoupper($platform) }}</p>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-400">Subtotal</dt><dd class="tabular-nums text-slate-200">${{ number_format($breakdown->subtotal, 2) }}</dd></div>
                    @if ($breakdown->discountAmount > 0)
                        <div class="flex justify-between"><dt class="text-slate-400">Coupon</dt><dd class="tabular-nums text-brand-400">−${{ number_format($breakdown->discountAmount, 2) }}</dd></div>
                    @endif
                    @if ($breakdown->pointsRedeemed > 0)
                        <div class="flex justify-between"><dt class="text-slate-400">Points ({{ number_format($breakdown->pointsRedeemed) }})</dt><dd class="tabular-nums text-brand-400">−${{ number_format($breakdown->pointsValue, 2) }}</dd></div>
                    @endif
                    <div class="mt-2 flex justify-between border-t border-ink-600 pt-3 text-base font-semibold">
                        <dt class="text-white">Total</dt><dd class="tabular-nums text-white">${{ number_format($breakdown->total, 2) }}</dd>
                    </div>
                </dl>

                <button type="button" wire:click="placeOrder" wire:loading.attr="disabled"
                        class="btn-primary mt-5">
                    <span wire:loading.remove wire:target="placeOrder">Place order</span>
                    <span wire:loading wire:target="placeOrder">Processing…</span>
                </button>
                <p class="mt-3 text-center text-xs text-slate-500">You'll get payment instructions on the next screen.</p>
            @else
                <p class="mt-4 text-sm text-slate-500">Select an account size to see pricing.</p>
            @endif
        </aside>
    </div>
</div>
