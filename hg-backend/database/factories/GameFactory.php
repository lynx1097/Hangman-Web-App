<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'word' => strtoupper(fake()->word()),
            'hint' => fake()->sentence(3),
            'category' => fake()->randomElement(['animal', 'country', 'food', 'plant', 'sport']),
            'guessed_letters' => [],
            'wrong_guesses' => 0,
            'status' => 'in_progress',
            'score' => 0,
        ];
    }
}
