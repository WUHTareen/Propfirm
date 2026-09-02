@php
    $badge = fn (string $s) => match ($s) {
        'approved' => 'bg-brand-500/15 text-brand-300',
        'rejected' => 'bg-red-500/15 text-red-300',
        default => 'bg-amber-500/15 text-amber-300',
    };

    // 100 points = $1.00 (see PointsService).
    $pointsToDollars = fn ($p) => '$'.number_format($p / 100, 2);

    $typeLabel = fn (string $t) => match ($t) {
        'phase_pass' => 'Phase passed',
        'funded' => 'Funded account',
        'payout' => 'Payout',
        default => 'Achievement',
    };

    $hasPending = $requests->contains(fn ($r) => $r->status === 'pending');
@endphp

<x-dashboard title="Achievement">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Achievement</h1>
        <p class="mt-1 text-sm text-slate-400">Certificates you have earned, and the rewards you have claimed against them.</p>
    </x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Certificates --}}
    <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">Your certificates</h2>

        @if ($certificates->isEmpty())
            <div class="mt-4 rounded-xl border border-dashed border-ink-600 px-5 py-10 text-center">
                <p class="text-sm text-slate-400">No certificates yet.</p>
                <p class="mt-1 text-sm text-slate-500">Pass a phase or reach a funded account and your certificate appears here automatically.</p>
                <a href="{{ route('dashboard.buynow') }}" class="mt-5 inline-flex rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Buy a challenge</a>
            </div>
        @else
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($certificates as $certificate)
                    <article class="rounded-xl border border-ink-700 bg-ink-900 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-medium text-slate-100">{{ $certificate->title }}</h3>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $certificate->certificate_number }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-brand-500/15 px-2.5 py-1 text-xs font-medium text-brand-300">{{ $typeLabel($certificate->type) }}</span>
                        </div>
                        <dl class="mt-4 flex items-end justify-between">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Issued</dt>
                                <dd class="mt-0.5 text-sm text-slate-300">{{ $certificate->issued_at?->format('d M Y') ?? '—' }}</dd>
                            </div>
                            @if ($certificate->amount !== null)
                                <div class="text-right">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Amount</dt>
                                    <dd class="mt-0.5 font-mono text-sm font-semibold tabular-nums text-white">${{ number_format($certificate->amount, 2) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Request Reward --}}
    <section x-data="{ open: false }" class="mt-6 rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-base font-semibold text-white">Request a reward</h2>
                <p class="mt-1 text-sm text-slate-400">Tell us what you have completed. An admin reviews it and sets the reward amount.</p>
            </div>

            @if ($hasPending)
                <span class="rounded-lg border border-ink-600 px-3.5 py-2 text-sm text-slate-500">Request under review</span>
            @else
                <button type="button" x-on:click="open = ! open"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-3.5 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Request Reward
                </button>
            @endif
        </div>

        @if (! $hasPending)
            <form method="POST" action="{{ route('dashboard.certificates.reward') }}"
                  x-show="open" x-cloak class="mt-5 grid gap-4 border-t border-ink-700 pt-5 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="category" class="field-label">Category</label>
                    <input id="category" name="category" type="text" class="field-input" value="{{ old('category') }}"
                           placeholder="e.g. Payout milestone" maxlength="80" required>
                </div>
                <div>
                    <label for="link" class="field-label">Link <span class="text-slate-500">(optional)</span></label>
                    <input id="link" name="link" type="url" class="field-input" value="{{ old('link') }}" placeholder="https://">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="field-label">Details</label>
                    <textarea id="description" name="description" rows="3" class="field-input" maxlength="1000"
                              placeholder="What did you achieve, and which account was it on?" required>{{ old('description') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary sm:w-auto sm:px-6">Submit request</button>
                </div>
            </form>
        @endif
    </section>

    {{-- History --}}
    <section class="mt-6 rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">Reward history</h2>

        @if ($requests->isEmpty())
            <p class="mt-3 text-sm text-slate-500">No reward requests yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-ink-700 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2 font-medium">Reward ID</th>
                            <th class="px-3 py-2 font-medium">Category</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 font-medium">Remarks</th>
                            <th class="px-3 py-2 font-medium">Amount</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach ($requests as $reward)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-300">RWD-{{ str_pad($reward->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-3 py-2 text-slate-200">{{ $reward->category ?? '—' }}</td>
                                <td class="px-3 py-2"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge($reward->status) }}">{{ ucfirst($reward->status) }}</span></td>
                                <td class="px-3 py-2 text-slate-400">{{ $reward->remarks ?: '—' }}</td>
                                <td class="px-3 py-2 tabular-nums text-slate-200">
                                    {{ $reward->status === 'approved' ? $pointsToDollars($reward->points_value) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-slate-400">{{ $reward->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-dashboard>
