<x-app-layout title="Admin">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <h1 class="font-display text-2xl font-bold text-white">Back office</h1>
            <span class="rounded-full bg-brand-500/15 px-2.5 py-1 font-mono text-[11px] uppercase tracking-wide text-brand-300">
                {{ auth()->user()->getRoleNames()->join(', ') }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-400">Signed in as staff.</p>
    </x-slot:header>

    <div class="rounded-2xl border border-ink-600 bg-ink-800 p-8">
        <h2 class="font-display text-lg font-semibold text-white">Admin panel coming in Phase 02</h2>
        <p class="mt-2 max-w-xl text-sm text-slate-400">
            This is a temporary staff landing that confirms roles and access control work. The full Filament
            admin panel — starting with the Challenge Plan Builder — lands in the next phase.
        </p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @php
                $modules = [
                    'Challenge Plan Builder' => 'manage challenge plans',
                    'User Management' => 'manage users',
                    'Orders' => 'manage orders',
                    'Withdrawals' => 'manage withdrawals',
                    'KYC Queue' => 'review kyc',
                    'Reports' => 'view reports',
                ];
            @endphp
            @foreach ($modules as $label => $permission)
                <div class="flex items-center justify-between rounded-lg border border-ink-700 bg-ink-900 px-4 py-3">
                    <span class="text-sm text-slate-300">{{ $label }}</span>
                    @can($permission)
                        <span class="font-mono text-[11px] uppercase tracking-wide text-brand-400">access</span>
                    @else
                        <span class="font-mono text-[11px] uppercase tracking-wide text-slate-600">no access</span>
                    @endcan
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
