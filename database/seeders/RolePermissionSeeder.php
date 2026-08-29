<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the four roles and a starter permission set.
 *
 * Roles:
 *  - admin   → full access (gets every permission)
 *  - support → user/KYC/ticket handling
 *  - finance → orders, withdrawals, payouts
 *  - trader  → the default role every registered customer gets
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Coarse-grained permissions for the back office. Fine-grained ones
        // can be added per module as those admin screens are built.
        $permissions = [
            'manage users',
            'manage challenge plans',
            'manage orders',
            'assign accounts',
            'manage phases',
            'review kyc',
            'manage withdrawals',
            'manage rewards',
            'manage coupons',
            'manage content',
            'view reports',
            'manage staff',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $support = Role::findOrCreate('support', 'web');
        $finance = Role::findOrCreate('finance', 'web');
        Role::findOrCreate('trader', 'web'); // no back-office permissions

        // Admin gets everything.
        $admin->syncPermissions(Permission::all());

        // Support: people, KYC, content.
        $support->syncPermissions([
            'manage users', 'review kyc', 'manage content', 'view reports',
        ]);

        // Finance: money movement.
        $finance->syncPermissions([
            'manage orders', 'manage withdrawals', 'manage rewards', 'view reports',
        ]);
    }
}
