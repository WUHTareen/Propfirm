<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Time-series of balance/equity per account. Powers the equity curve chart
 * and drawdown calculations. One row per sync (manual now, MetaApi later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equity_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_account_id')->constrained()->cascadeOnDelete();

            $table->decimal('balance', 14, 2);
            $table->decimal('equity', 14, 2);
            $table->decimal('open_pnl', 14, 2)->nullable();
            $table->decimal('drawdown_percent', 6, 2)->nullable();
            $table->decimal('daily_drawdown_percent', 6, 2)->nullable();

            $table->enum('source', ['manual', 'metaapi'])->default('manual');
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['trading_account_id', 'snapshot_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equity_snapshots');
    }
};
