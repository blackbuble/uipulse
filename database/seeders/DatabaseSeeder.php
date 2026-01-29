<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ShieldSeeder::class);

        // Create Admin User
        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@uipulse.ai',
            'password' => bcrypt('password'),
        ]);

        $admin->assignRole('super_admin');

        // Create sample projects for the admin
        \App\Models\Project::factory(3)
            ->for($admin)
            ->has(
                \App\Models\Design::factory(5)
                    ->has(\App\Models\AiAnalysis::factory(2), 'aiAnalyses')
            )
            ->create();

        // Create random users with projects
        \App\Models\User::factory(5)
            ->has(
                \App\Models\Project::factory(2)
                    ->has(
                        \App\Models\Design::factory(3)
                            ->has(\App\Models\AiAnalysis::factory(1), 'aiAnalyses')
                    )
            )
            ->create();
    }
}
