<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video-review / social-media / task submissions reviewed by admin.
 * On approval, a reward_points credit is created. Some types are one-per-user
 * (enforced in the application layer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['video_review', 'social_media', 'task']);
            $table->enum('platform', ['instagram', 'tiktok', 'facebook'])->nullable();
            $table->string('file_path')->nullable(); // uploaded video object key
            $table->string('link')->nullable();      // social post URL
            $table->text('description')->nullable();

            $table->unsignedInteger('points_value')->default(0); // 500 / 300 etc.
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_submissions');
    }
};
