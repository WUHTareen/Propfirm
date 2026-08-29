<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One affiliate profile per user. Aggregated click/signup/conversion counters
 * and commission balances live here; individual events live in `referrals`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // affiliate/referral code

            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('signups')->default(0);
            $table->unsignedInteger('conversions')->default(0);

            $table->decimal('commission_rate', 5, 2)->default(0); // percent of order
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->decimal('available_commission', 12, 2)->default(0);
            $table->decimal('paid_commission', 12, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
