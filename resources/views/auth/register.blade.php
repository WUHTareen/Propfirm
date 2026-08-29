<x-guest-layout title="Create account">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Create your account</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400">Start your funded-trader journey.</p>

        <x-auth-errors />

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="field-label">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       required autofocus autocomplete="name" class="field-input" placeholder="Jane Trader">
            </div>

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       required autocomplete="username" class="field-input" placeholder="you@example.com">
            </div>

            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" name="password" type="password"
                       required autocomplete="new-password" class="field-input" placeholder="At least 8 characters">
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       required autocomplete="new-password" class="field-input" placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn-primary">Create account</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            Already have an account? <a href="{{ route('login') }}" class="link-muted font-medium">Sign in</a>
        </p>
    </div>
</x-guest-layout>
