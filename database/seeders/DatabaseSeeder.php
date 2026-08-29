<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the starter catalogue and content.
     */
    public function run(): void
    {
        $this->call([
            ChallengePlanSeeder::class,
            SettingSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
