<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Hangman Game API",
 *     version="1.0.0",
 *     description="REST API for the Hangman Game — handles authentication, game sessions, and leaderboard."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Use the Bearer token returned by /auth/login or /auth/register."
 * )
 *
 * @OA\Schema(
 *     schema="UserResource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="jane@example.com")
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     @OA\Property(property="message", type="string", example="Login successful"),
 *     @OA\Property(property="access_token", type="string", example="1|abc..."),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="user", ref="#/components/schemas/UserResource")
 * )
 *
 * @OA\Schema(
 *     schema="GameState",
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="masked_word", type="string", example="_ A _ G _ A _"),
 *     @OA\Property(property="word_length", type="integer", example=7),
 *     @OA\Property(property="guessed_letters", type="array", @OA\Items(type="string"), example={"A","G"}),
 *     @OA\Property(property="wrong_guesses", type="integer", example=2),
 *     @OA\Property(property="max_wrong_guesses", type="integer", example=6),
 *     @OA\Property(property="remaining_attempts", type="integer", example=4),
 *     @OA\Property(property="status", type="string", enum={"in_progress","won","lost"}, example="in_progress"),
 *     @OA\Property(property="score", type="integer", example=0),
 *     @OA\Property(property="elapsed_seconds", type="integer", nullable=true, example=null),
 *     @OA\Property(property="hint", type="string", nullable=true, example="A large grey mammal"),
 *     @OA\Property(property="category", type="string", example="animal"),
 *     @OA\Property(property="word", type="string", nullable=true, description="Only present when the game is finished", example="ELEPHANT"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="LeaderboardEntry",
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="total_score", type="integer", example=1250),
 *     @OA\Property(property="games_won", type="integer", example=10),
 *     @OA\Property(property="games_played", type="integer", example=14)
 * )
 */
abstract class Controller
{
    //
}
