<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The MT5/MT4 accounts assigned to traders. Holds credentials (encrypted),
 * current phase/status, and the rule thresholds resolved from the plan.
 * Credentials are cast as `encrypted` on the model, never stored in plaintext.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('challenge_plan_id')->nullable()
                ->constrained('challenge_plans')->nullOnDelete();

            // Broker credentials (encrypted at rest via model cast).
            $table->string('login')->nullable();           // MT5/MT4 login
            $table->text('password')->nullable();          // master password (encrypted)
            $table->text('investor_password')->nullable(); // read-only password (encrypted)
            $table->string('server')->nullable();          // broker server name
            $table->enum('platform', ['mt5', 'mt4']);

            $table->decimal('account_size', 12, 2);
            $table->enum('challenge_type', ['two_step', 'one_step', 'instant']);
            $table->unsignedTinyInteger('current_phase')->default(1);

            $table->enum('status', [
                'pending_assignment', 'active', 'passed', 'breached', 'funded', 'disabled',
            ])->default('pending_assignment');

            // Resolved rule thresholds (copied from plan at assignment).
            $table->decimal('starting_balance', 14, 2)->nullable();
            $table->decimal('current_balance', 14, 2)->nullable();
            $table->decimal('current_equity', 14, 2)->nullable();
            $table->decimal('profit_target_amount', 14, 2)->nullable();
            $table->decimal('daily_drawdown_limit', 14, 2)->nullable();
            $table->decimal('max_drawdown_limit', 14, 2)->nullable();
            $table->decimal('highest_balance', 14, 2)->nullable(); // for trailing drawdown

            // Daily-drawdown baseline (snapshot at the daily reset time).
            $table->decimal('day_start_balance', 14, 2)->nullable();
            $table->timestamp('day_start_at')->nullable();

            $table->unsignedSmallInteger('trading_days_count')->default(0);
            $table->decimal('profit_split_percent', 5, 2)->nullable();

            // Lifecycle timestamps + reason codes.
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('passed_at')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('breached_at')->nullable();
            $table->string('breach_reason')->nullable();

            // Automation hook (Module D, Option 2) — filled when MetaApi is wired.
            $table->string('metaapi_account_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('login');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_accounts');
    }
};
