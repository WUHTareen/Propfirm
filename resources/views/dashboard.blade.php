<x-app-layout title="Overview">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-slate-400">Your account overview.</p>
    </x-slot:header>

    <x-auth-errors />

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
        <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-brand-500/40 px-5 py-2.5 font-semibold text-ink-950/70">
            Buy a challenge
            <span class="rounded bg-ink-950/20 px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-wide">Phase 03</span>
        </span>
    </div>
</x-app-layout>
