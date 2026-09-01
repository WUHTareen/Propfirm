@php
    $catLabels = [
        'general' => 'General', 'accounts' => 'Accounts', 'payments' => 'Payments',
        'payouts' => 'Payouts', 'kyc' => 'KYC', 'rewards' => 'Rewards',
    ];
@endphp

<x-marketing title="FAQ" description="Answers to common questions about challenges, accounts, payments and payouts.">
    <section class="mx-auto max-w-3xl px-6 py-16">
        <div class="text-center">
            <h1 class="font-display text-4xl font-extrabold text-white">Frequently asked questions</h1>
            <p class="mt-3 text-slate-400">Can't find an answer? <a href="{{ route('contact') }}" class="text-brand-400 hover:text-brand-300">Contact us</a>.</p>
        </div>

        <div class="mt-10 space-y-10">
            @forelse ($grouped as $category => $items)
                <div>
                    <h2 class="mb-3 font-display text-sm font-semibold uppercase tracking-wide text-brand-300">{{ $catLabels[$category] ?? ucfirst($category) }}</h2>
                    <div class="divide-y divide-ink-800 overflow-hidden rounded-2xl border border-ink-700 bg-ink-900">
                        @foreach ($items as $faq)
                            <details class="group px-5 py-4">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-medium text-slate-100">
                                    {{ $faq->question }}
                                    <svg class="h-5 w-5 shrink-0 text-slate-500 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                </summary>
                                <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $faq->answer }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-center text-sm text-slate-500">No FAQs published yet.</p>
            @endforelse
        </div>
    </section>
</x-marketing>
