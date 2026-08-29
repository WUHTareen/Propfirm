<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only points ledger. 100 points = $1.00. `points` is signed:
 * positive = credit, negative = debit. users.points_balance caches the sum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['earn', 'spend', 'transfer_in', 'transfer_out', 'adjustment']);
            $table->enum('source', [
                'cashback', 'video_review', 'social_media', 'giveaway',
                'referral', 'purchase_redemption', 'transfer', 'admin',
            ]);
            $table->integer('points'); // signed: +credit / -debit
            $table->integer('balance_after')->nullable();
            $table->string('description')->nullable();

            // Loose polymorphic reference to the originating record.
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('related_user_id')->nullable()   // counterparty in a transfer
                ->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()        // admin who made an adjustment
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_points');
    }
};
