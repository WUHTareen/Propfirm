<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the first admin account so the back office can be opened.
 * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD in .env (with safe
 * defaults for local dev). Change the password immediately in production.
 *
 * Note: email_verified_at is set via a direct property (not mass assignment)
 * because it is intentionally not in the User model's $fillable. The password
 * is set raw and hashed once by the model's 'hashed' cast — never pre-hash it.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@example.com';
        // env() returns '' (not the default) for a present-but-empty key, so
        // coalesce empties to the fallback too.
        $password = env('ADMIN_PASSWORD') ?: 'ChangeMe!12345';

        $admin = User::withTrashed()->firstOrNew(['email' => $email]);

        $admin->name = env('ADMIN_NAME') ?: 'Administrator';
        $admin->is_active = true;
        $admin->deleted_at = null;

        // Only set the password when first creating the account, so re-seeding
        // never clobbers a password the admin has since changed.
        if (! $admin->exists) {
            $admin->password = $password; // 'hashed' cast hashes this once
        }

        if (! $admin->referral_code) {
            $admin->referral_code = 'ADMIN-'.Str::upper(Str::random(6));
        }

        if (is_null($admin->email_verified_at)) {
            $admin->email_verified_at = now();
        }

        $admin->save();

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->newLine();
        $this->command->info("Admin ready → {$email} (verified)");
        if ($password === 'ChangeMe!12345' && $admin->wasRecentlyCreated) {
            $this->command->warn('Using the DEFAULT admin password. Set ADMIN_PASSWORD in .env before seeding, or change it after logging in.');
        }
    }
}
