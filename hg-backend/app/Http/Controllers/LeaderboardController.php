<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaderboardEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
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