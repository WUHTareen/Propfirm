<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual referral events (click -> signup -> converted) with the commission
 * earned once the referred user makes a qualifying purchase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->enum('status', ['click', 'signup', 'converted'])->default('click');
            $table->string('ip_address', 45)->nullable();
            $table->string('landing_url')->nullable();
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
