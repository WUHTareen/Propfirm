<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Achievement / payout certificates, auto-generated on qualifying milestones
 * (phase pass, funded, payout). The rendered PDF lives on object storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trading_account_id')->nullable()
                ->constrained('trading_accounts')->nullOnDelete();

            $table->enum('type', ['phase_pass', 'funded', 'payout', 'achievement']);
            $table->string('title');
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('file_path')->nullable(); // generated PDF object key
            $table->json('metadata')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
