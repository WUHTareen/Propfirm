<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The single source of truth for pricing AND evaluation rules.
 * Powers both the public pricing tables and the dashboard rule display.
 * Everything here is admin-configurable via the Challenge Plan Builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "2-Step $10,000"
            $table->string('slug')->unique();

            $table->enum('challenge_type', ['two_step', 'one_step', 'instant']);
            $table->decimal('account_size', 12, 2);   // 3000 .. 200000
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('USD');

            // Phase config as JSON so any phase count / target is possible:
            // [{ "phase": 1, "profit_target_percent": 7, "min_trading_days": 4 }, ...]
            $table->json('phases')->nullable();

            // Convenience columns mirrored from `phases` for querying/display.
            $table->decimal('phase1_target_percent', 5, 2)->nullable();
            $table->decimal('phase2_target_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('min_trading_days')->default(0);

            // Risk rules.
            $table->decimal('daily_drawdown_percent', 5, 2)->default(5);
            $table->decimal('max_drawdown_percent', 5, 2)->default(10);
            $table->enum('drawdown_type', ['static', 'trailing'])->default('static');
            $table->unsignedInteger('leverage')->default(100); // 100 => 1:100

            // Consistency rule (optional).
            $table->boolean('has_consistency_rule')->default(false);
            $table->decimal('consistency_percent', 5, 2)->nullable();

            // Profit split paid to the trader once funded.
            $table->decimal('profit_split_percent', 5, 2)->default(80);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['challenge_type', 'is_active']);
            $table->index('account_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_plans');
    }
};
