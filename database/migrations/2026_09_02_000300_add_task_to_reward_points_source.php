<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Approving a "Request Reward" credits points with source = 'task', which the
 * original enum did not allow — MySQL truncated the value and the insert
 * failed. Widen the enum to match the reward_submissions types.
 *
 * Raw SQL because altering an enum in place is MySQL-specific; other drivers
 * do not enforce the constraint, so there is nothing to widen there.
 */
return new class extends Migration
{
    private const SOURCES_WITH_TASK = "'cashback','video_review','social_media','task','giveaway','referral','purchase_redemption','transfer','admin'";

    private const SOURCES_ORIGINAL = "'cashback','video_review','social_media','giveaway','referral','purchase_redemption','transfer','admin'";

    public function up(): void
    {
        if (! $this->onMySql()) {
            return;
        }

        DB::statement('ALTER TABLE `reward_points` MODIFY `source` ENUM('.self::SOURCES_WITH_TASK.') NOT NULL');
    }

    public function down(): void
    {
        if (! $this->onMySql()) {
            return;
        }

        // Existing task credits would no longer be representable — park them on
        // 'admin' so the column can narrow again without losing the ledger row.
        DB::table('reward_points')->where('source', 'task')->update(['source' => 'admin']);

        DB::statement('ALTER TABLE `reward_points` MODIFY `source` ENUM('.self::SOURCES_ORIGINAL.') NOT NULL');
    }

    private function onMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
