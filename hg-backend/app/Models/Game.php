<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'word',
        'hint',
        'category',
        'guessed_letters',
        'wrong_guesses',
        'status',
        'score',
    ];

    protected $casts = [
        'guessed_letters' => 'array',
        'wrong_guesses'   => 'integer',
        'score'           => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
