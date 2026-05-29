<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_score',
        'games_won',
        'games_played'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}