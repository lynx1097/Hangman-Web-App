<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'word',
        'guessed_letters',
        'wrong_guesses',
        'status',
        'score'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
