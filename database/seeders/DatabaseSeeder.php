<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * When APP_ENV=demo the DemoSeeder is also called, which creates realistic
     * job listings, recruiter/HR accounts, and 8 candidates per job at various
     * pipeline stages for demonstration and testing purposes.
     */
    public function run(): void
    {
        $this->call([
            PromptSeeder::class,
            SkillSeeder::class,
            UserSeeder::class,
            JobSeeder::class,
        ]);

        if (app()->environment('demo')) {
            $this->call(DemoSeeder::class);
        }
    }
}
