<x-guest-layout title="Confirm password">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Confirm your password</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400">This is a secure area. Please confirm your password to continue.</p>

        <x-auth-errors />

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" name="password" type="password"
                       required autofocus autocomplete="current-password" class="field-input">
            </div>

            <button type="submit" class="btn-primary">Confirm</button>
        </form>
    </div>
</x-guest-layout>
