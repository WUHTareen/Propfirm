<x-guest-layout title="Set new password">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Set a new password</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400">Choose a strong password for your account.</p>

        <x-auth-errors />

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                       required autofocus autocomplete="username" class="field-input">
            </div>

            <div>
                <label for="password" class="field-label">New password</label>
                <input id="password" name="password" type="password"
                       required autocomplete="new-password" class="field-input" placeholder="At least 8 characters">
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       required autocomplete="new-password" class="field-input">
            </div>

            <button type="submit" class="btn-primary">Reset password</button>
        </form>
    </div>
</x-guest-layout>
