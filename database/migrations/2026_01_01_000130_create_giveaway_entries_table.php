<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weekly Trustpilot-review giveaway. Entries are grouped by week_start; the
 * admin draws random winners who receive a free account of a given size.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giveaway_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start'); // Monday of the giveaway week
            $table->string('trustpilot_review_link')->nullable();

            $table->enum('status', ['entered', 'won', 'lost'])->default('entered');
            $table->decimal('prize_account_size', 12, 2)->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
            $table->index(['week_start', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giveaway_entries');
    }
};
