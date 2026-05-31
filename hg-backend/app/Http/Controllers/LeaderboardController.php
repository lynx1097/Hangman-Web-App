<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaderboardEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/leaderboard",
     *     summary="Get the top-10 leaderboard",
     *     tags={"Leaderboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Top 10 players ordered by total score",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/LeaderboardEntry"))
     *     )
     * )
     */
    public function index()
    {
        $leaderboard = LeaderboardEntry::with('user')
            ->orderBy('total_score', 'desc')
            ->take(10)
            ->get()
            ->map(function ($entry) {
                return [
                    'name' => $entry->user->name,
                    'total_score' => $entry->total_score,
                    'games_won' => $entry->games_won,
                    'games_played' => $entry->games_played,
                ];
            });

        return response()->json($leaderboard);
    }

    /**
     * @OA\Get(
     *     path="/api/leaderboard/users/{user}",
     *     summary="Get leaderboard stats for a specific user",
     *     tags={"Leaderboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *     @OA\Response(response=200, description="User leaderboard stats", @OA\JsonContent(ref="#/components/schemas/LeaderboardEntry")),
     *     @OA\Response(response=404, description="User not found")
     * )
     *
     * Show a single user's leaderboard stats.
     */
    public function show(User $user)
    {
        $entry = LeaderboardEntry::firstOrCreate(
            ['user_id' => $user->id],
            ['total_score' => 0, 'games_won' => 0, 'games_played' => 0]
        );

        return response()->json([
            'name' => $user->name,
            'total_score' => $entry->total_score,
            'games_won' => $entry->games_won,
            'games_played' => $entry->games_played,
        ]);
    }
}