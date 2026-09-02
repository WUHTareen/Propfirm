<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Request Reward" on the Achievement page raises a task submission, and the
 * history table shows which category it was filed under. Video-review and
 * social-media submissions leave this null — their type already says it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_submissions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('reward_submissions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
