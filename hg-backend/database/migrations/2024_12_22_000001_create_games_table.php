<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            // Owning user. constrained() also creates an index on user_id.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The word the player must guess.
            $table->string('word');
            // Letters guessed so far (cast to array on the Game model).
            $table->json('guessed_letters')->nullable();
            // Number of wrong guesses (0-255 is plenty for a hangman round).
            $table->unsignedTinyInteger('wrong_guesses')->default(0);
            // in_progress | won | lost
            $table->string('status')->default('in_progress');
            // Score earned for this game.
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
