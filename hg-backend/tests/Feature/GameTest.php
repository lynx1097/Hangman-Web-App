<?php

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to list games', function () {
    $this->getJson('/api/games')->assertUnauthorized();
});

it('starts a new game from the word API with a masked word and a hidden answer', function () {
    Http::fake([
        'wordgamedb.com/*' => Http::response([
            '_id' => 'abc123', 'word' => 'hamster', 'category' => 'animal',
            'numLetters' => 7, 'numSyllables' => 2, '__v' => 0, 'hint' => 'Pet rodent',
        ]),
    ]);

    Sanctum::actingAs(User::factory()->create());

    $res = $this->postJson('/api/games')->assertCreated();

    $res->assertJson(fn ($json) => $json
        ->where('status', 'in_progress')
        ->where('wrong_guesses', 0)
        ->where('guessed_letters', [])
        ->where('remaining_attempts', 6)
        ->where('hint', 'Pet rodent')      // hint IS sent to the client
        ->where('word_length', 7)
        ->missing('word')                  // the answer is NOT
        ->etc()
    );

    expect($res->json('masked_word'))->toBe('_ _ _ _ _ _ _');
});

it('falls back to the local word list when the API is unavailable', function () {
    Http::fake([
        'wordgamedb.com/*' => Http::response(null, 503),
    ]);

    Sanctum::actingAs(User::factory()->create());

    $res = $this->postJson('/api/games')->assertCreated();

    expect($res->json('status'))->toBe('in_progress');
    expect($res->json('hint'))->not->toBeNull();          // local list provides a hint
    expect($res->json('masked_word'))->toContain('_');
    expect($res->json())->not->toHaveKey('word');
});

it('reveals correct letters and counts wrong guesses', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create(['word' => 'GO', 'guessed_letters' => []]);

    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'g'])
        ->assertOk()
        ->assertJsonPath('wrong_guesses', 0)
        ->assertJsonPath('masked_word', 'G _');

    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'z'])
        ->assertOk()
        ->assertJsonPath('wrong_guesses', 1);
});

it('marks the game won, reveals the word, and updates the leaderboard', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create(['word' => 'GO']);

    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'G'])->assertOk();
    $res = $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'O'])->assertOk();

    $res->assertJsonPath('status', 'won')->assertJsonPath('word', 'GO');
    expect($res->json('score'))->toBeGreaterThan(0);

    $entry = $user->leaderboardEntry()->first();
    expect($entry->games_played)->toBe(1);
    expect($entry->games_won)->toBe(1);
    expect($entry->total_score)->toBe($res->json('score'));
});

it('marks the game lost after six wrong guesses', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create(['word' => 'GO']);

    $res = null;
    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $letter) {
        $res = $this->postJson("/api/games/{$game->id}/guesses", ['letter' => $letter])->assertOk();
    }

    $res->assertJsonPath('status', 'lost')->assertJsonPath('score', 0);

    $entry = $user->leaderboardEntry()->first();
    expect($entry->games_played)->toBe(1);
    expect($entry->games_won)->toBe(0);
    expect($entry->total_score)->toBe(0);
});

it('rejects guesses on a finished game', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create(['word' => 'GO', 'status' => 'won']);

    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'G'])->assertStatus(422);
});

it('rejects a duplicate letter', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create(['word' => 'GO', 'guessed_letters' => ['G']]);

    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'G'])->assertStatus(422);
});

it('rejects invalid letters', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create(['word' => 'GO']);

    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => '1'])->assertStatus(422);
    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'ab'])->assertStatus(422);
});

it("forbids acting on another user's game", function () {
    $owner = User::factory()->create();
    $game = Game::factory()->for($owner)->create(['word' => 'GO']);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/games/{$game->id}")->assertForbidden();
    $this->postJson("/api/games/{$game->id}/guesses", ['letter' => 'G'])->assertForbidden();
    $this->deleteJson("/api/games/{$game->id}")->assertForbidden();
});

it('deletes a game the user owns', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $game = Game::factory()->for($user)->create();

    $this->deleteJson("/api/games/{$game->id}")->assertNoContent();
    expect(Game::find($game->id))->toBeNull();
});
