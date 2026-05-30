<?php

namespace Database\Factories;

use App\Models\LeaderboardEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaderboardEntry>
 */
class LeaderboardEntryFactory extends Factory
{
    protected $model = LeaderboardEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gamesPlayed = fake()->numberBetween(0, 100);
        $gamesWon = fake()->numberBetween(0, $gamesPlayed);

        return [
            'user_id' => User::factory(),
            'total_score' => $gamesWon * fake()->numberBetween(5, 20),
            'games_won' => $gamesWon,
            'games_played' => $gamesPlayed,
        ];
    }
}
