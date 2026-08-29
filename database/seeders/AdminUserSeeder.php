<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the first admin account so the back office can be opened.
 * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD in .env (with safe
 * defaults for local dev). Change the password immediately in production.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@example.com';
        // env() returns '' (not the default) for a present-but-empty key, so
        // coalesce empties to the fallback too.
        $password = env('ADMIN_PASSWORD') ?: 'ChangeMe!12345';

        $admin = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
                'referral_code' => 'ADMIN-'.Str::upper(Str::random(6)),
                'deleted_at' => null,
            ],
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->newLine();
        $this->command->info("Admin ready → {$email}");
        if ($password === 'ChangeMe!12345') {
            $this->command->warn('Using the DEFAULT admin password. Set ADMIN_PASSWORD in .env and re-seed, or change it after logging in.');
        }
    }
}
