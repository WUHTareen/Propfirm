@php
    $user = auth()->user();
    $twoFactorPending = ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
    $twoFactorEnabled = ! is_null($user->two_factor_confirmed_at);

    $statusMessages = [
        'profile-information-updated' => 'Profile updated.',
        'password-updated' => 'Password changed.',
        'two-factor-authentication-enabled' => 'Two-factor enabled — scan the QR code and confirm below.',
        'two-factor-authentication-confirmed' => 'Two-factor authentication is now active.',
        'two-factor-authentication-disabled' => 'Two-factor authentication disabled.',
        'recovery-codes-generated' => 'New recovery codes generated.',
    ];
@endphp

<x-app-layout title="Profile">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Profile &amp; security</h1>
        <p class="mt-1 text-sm text-slate-400">Manage your account details, password and two-factor authentication.</p>
    </x-slot:header>

    @if (session('status') && isset($statusMessages[session('status')]))
        <div class="mb-6 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">
            {{ $statusMessages[session('status')] }}
        </div>
    @endif

    <x-auth-errors />

    <div class="grid gap-6">

        {{-- Profile information --}}
        <section class="rounded-2xl border border-ink-600 bg-ink-800 p-6">
            <h2 class="font-display text-lg font-semibold text-white">Profile information</h2>
            <p class="mt-1 mb-5 text-sm text-slate-400">Update your name and email address.</p>

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="grid max-w-lg gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="field-input">
                </div>
                <div>
                    <label for="pemail" class="field-label">Email</label>
                    <input id="pemail" name="email" type="email" value="{{ old('email', $user->email) }}" required class="field-input">
                </div>
                <div><button type="submit" class="btn-primary sm:w-auto sm:px-6">Save changes</button></div>
            </form>
        </section>

        {{-- Password --}}
        <section class="rounded-2xl border border-ink-600 bg-ink-800 p-6">
            <h2 class="font-display text-lg font-semibold text-white">Update password</h2>
            <p class="mt-1 mb-5 text-sm text-slate-400">Use a long, unique password to keep your account secure.</p>

            <form method="POST" action="{{ route('user-password.update') }}" class="grid max-w-lg gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="field-label">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="field-input">
                </div>
                <div>
                    <label for="npassword" class="field-label">New password</label>
                    <input id="npassword" name="password" type="password" autocomplete="new-password" class="field-input">
                </div>
                <div>
                    <label for="npassword_confirmation" class="field-label">Confirm new password</label>
                    <input id="npassword_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="field-input">
                </div>
                <div><button type="submit" class="btn-primary sm:w-auto sm:px-6">Update password</button></div>
            </form>
        </section>

        {{-- Two-factor authentication --}}
        <section class="rounded-2xl border border-ink-600 bg-ink-800 p-6">
            <div class="flex items-center gap-3">
                <h2 class="font-display text-lg font-semibold text-white">Two-factor authentication</h2>
                @if ($twoFactorEnabled)
                    <span class="rounded-full bg-brand-500/15 px-2.5 py-0.5 font-mono text-[11px] uppercase tracking-wide text-brand-300">On</span>
                @else
                    <span class="rounded-full bg-ink-700 px-2.5 py-0.5 font-mono text-[11px] uppercase tracking-wide text-slate-400">Off</span>
                @endif
            </div>
            <p class="mt-1 mb-5 text-sm text-slate-400">Add an extra layer of security using an authenticator app (Google Authenticator, Authy, 1Password).</p>

            @if (! $user->two_factor_secret)
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button type="submit" class="btn-primary sm:w-auto sm:px-6">Enable two-factor</button>
                </form>

            @elseif ($twoFactorPending)
                <div class="grid gap-5 sm:grid-cols-[auto,1fr] sm:items-start">
                    <div class="rounded-xl bg-white p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <div>
                        <p class="text-sm text-slate-300">Scan this QR code with your authenticator app, then enter the 6-digit code to finish enabling.</p>
                        <div class="mt-4 flex max-w-xs flex-col gap-3">
                            <form method="POST" action="{{ route('two-factor.confirm') }}" id="confirm-2fa" class="flex flex-col gap-3">
                                @csrf
                                <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                                       class="field-input font-mono tracking-widest" placeholder="000000" required>
                            </form>
                            <div class="flex gap-2">
                                <button type="submit" form="confirm-2fa" class="btn-primary sm:w-auto sm:px-6">Confirm</button>
                                <form method="POST" action="{{ route('two-factor.disable') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-ink-600 px-4 py-2.5 text-sm text-slate-300 transition hover:bg-ink-700">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <div class="rounded-xl border border-ink-700 bg-ink-900 p-4">
                    <p class="text-sm font-medium text-slate-200">Recovery codes</p>
                    <p class="mb-3 mt-1 text-xs text-slate-400">Store these somewhere safe. Each can be used once if you lose your device.</p>
                    <div class="grid grid-cols-2 gap-1.5 font-mono text-sm text-brand-300">
                        @foreach ($user->recoveryCodes() as $code)
                            <span>{{ $code }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-ink-600 px-4 py-2.5 text-sm text-slate-300 transition hover:bg-ink-700">Regenerate codes</button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-500/40 px-4 py-2.5 text-sm text-red-300 transition hover:bg-red-500/10">Disable two-factor</button>
                    </form>
                </div>
            @endif
        </section>

    </div>
</x-app-layout>
