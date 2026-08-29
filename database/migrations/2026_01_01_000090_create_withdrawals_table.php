<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payout requests. Eligibility (KYC, phase, trading days) is snapshotted at
 * request time so an admin reviews the state as it was when submitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_number')->unique(); // e.g. WD-2026-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trading_account_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->string('method'); // usdt_bsc, usdt_trc20, btc, usdc_eth, bank...
            $table->string('wallet_address');
            $table->string('network')->nullable();

            $table->enum('status', [
                'pending', 'under_review', 'approved', 'processing', 'paid', 'rejected',
            ])->default('pending');

            $table->json('eligibility_snapshot')->nullable(); // kyc/phase/trading_days at request
            $table->text('remarks')->nullable();
            $table->string('transaction_reference')->nullable(); // payout txid

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
