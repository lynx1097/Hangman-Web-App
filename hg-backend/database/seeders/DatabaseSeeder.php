<?php

namespace Database\Seeders;

use App\Models\LeaderboardEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primary test account with a zeroed leaderboard entry (mirrors what
        // AuthController@register creates for real signups).
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        LeaderboardEntry::factory()->create([
            'user_id' => $testUser->id,
            'total_score' => 0,
            'games_won' => 0,
            'games_played' => 0,
        ]);

        // A handful of additional players so GET /api/leaderboard returns a
        // populated top-10 ordered by total_score.
        User::factory(9)->create()->each(function (User $user) {
            LeaderboardEntry::factory()->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
