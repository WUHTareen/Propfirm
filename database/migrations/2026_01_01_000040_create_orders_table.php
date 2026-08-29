<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase records. Crypto-first checkout. A plan_snapshot is stored so the
 * order is immutable even if the challenge_plan is later edited or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g. ORD-2026-0001

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_plan_id')->nullable()
                ->constrained('challenge_plans')->nullOnDelete();
            $table->json('plan_snapshot')->nullable(); // name/size/price/rules at purchase time

            $table->enum('platform', ['mt5', 'mt4']);
            $table->decimal('account_size', 12, 2);

            // Money.
            $table->decimal('subtotal', 10, 2);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->unsignedInteger('points_redeemed')->default(0);
            $table->decimal('points_value', 10, 2)->default(0); // $ value of redeemed points
            $table->boolean('cashback_opt_in')->default(false);
            $table->decimal('total', 10, 2);
            $table->char('currency', 3)->default('USD');

            $table->enum('status', [
                'pending', 'paid', 'processing', 'completed',
                'cancelled', 'refunded', 'expired',
            ])->default('pending');

            // Crypto payment details.
            $table->string('payment_method')->nullable(); // usdt_bsc, usdt_trc20, btc, usdc_eth...
            $table->string('payment_gateway')->nullable(); // nowpayments, coinpayments, cryptomus
            $table->string('payment_reference')->nullable(); // gateway invoice id
            $table->string('payment_txid')->nullable();      // blockchain tx hash
            $table->decimal('crypto_amount', 24, 8)->nullable();
            $table->string('crypto_currency')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
