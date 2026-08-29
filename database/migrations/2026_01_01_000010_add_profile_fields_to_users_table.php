<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the default users table with trader profile, referral and
 * cached-balance fields. Auth/2FA columns are added by Fortify's own
 * migration; roles are handled by Spatie laravel-permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('country', 2)->nullable()->after('phone'); // ISO 3166-1 alpha-2
            $table->string('avatar_path')->nullable()->after('country');

            // Cached cashback points balance. Source of truth is the
            // reward_points ledger; this column is a denormalised cache.
            $table->integer('points_balance')->default(0)->after('avatar_path');

            // Referral: every user gets a code; referred_by links to the referrer.
            $table->string('referral_code')->nullable()->unique()->after('points_balance');
            $table->foreignId('referred_by')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();

            // Account-wide KYC status (individual documents tracked separately).
            $table->enum('kyc_status', ['unverified', 'pending', 'approved', 'rejected'])
                ->default('unverified')->after('referred_by');

            $table->boolean('is_active')->default(true)->after('kyc_status');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'phone', 'country', 'avatar_path', 'points_balance',
                'referral_code', 'referred_by', 'kyc_status', 'is_active',
                'last_login_at', 'deleted_at',
            ]);
        });
    }
};
