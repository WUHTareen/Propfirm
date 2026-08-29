<x-guest-layout title="Two-factor">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Two-factor authentication</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400" id="tf-hint">
            Enter the 6-digit code from your authenticator app.
        </p>

        <x-auth-errors />

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <div id="tf-code">
                <label for="code" class="field-label">Authentication code</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                       autofocus class="field-input font-mono tracking-widest" placeholder="000000">
            </div>

            <div id="tf-recovery" hidden>
                <label for="recovery_code" class="field-label">Recovery code</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                       class="field-input font-mono" placeholder="xxxxxxxx-xxxxxxxx">
            </div>

            <button type="submit" class="btn-primary">Verify</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            <button type="button" id="tf-toggle" class="link-muted font-medium">Use a recovery code instead</button>
        </p>
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('tf-toggle');
            var codeBlock = document.getElementById('tf-code');
            var recoveryBlock = document.getElementById('tf-recovery');
            var hint = document.getElementById('tf-hint');
            var usingRecovery = false;

            toggle.addEventListener('click', function () {
                usingRecovery = !usingRecovery;
                codeBlock.hidden = usingRecovery;
                recoveryBlock.hidden = !usingRecovery;
                document.getElementById('code').value = '';
                document.getElementById('recovery_code').value = '';
                if (usingRecovery) {
                    toggle.textContent = 'Use an authentication code instead';
                    hint.textContent = 'Enter one of your saved recovery codes.';
                    document.getElementById('recovery_code').focus();
                } else {
                    toggle.textContent = 'Use a recovery code instead';
                    hint.textContent = 'Enter the 6-digit code from your authenticator app.';
                    document.getElementById('code').focus();
                }
            });
        })();
    </script>
</x-guest-layout>
