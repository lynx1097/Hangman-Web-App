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
}