<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use App\Services\WordService;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Maximum number of wrong guesses before the game is lost.
     */
    private const MAX_WRONG = 6;

    /**
     * List the authenticated user's games (most recent first).
     */
    public function index(Request $request)
    {
        $games = $request->user()->games()
            ->latest()
            ->get()
            ->map(fn (Game $game) => $this->state($game, $game->status !== 'in_progress'));

        return response()->json($games);
    }

    /**
     * Start a new game for the authenticated user with a random word.
     *
     * Optional difficulty filters (forwarded to the Word Game DB API):
     *   category   - one of: animal, country, food, plant, sport
     *   minLetters - minimum word length
     *   maxLetters - maximum word length
     */
    public function store(Request $request, WordService $words)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'in:animal,country,food,plant,sport'],
            'minLetters' => ['nullable', 'integer', 'min:1', 'max:20'],
            'maxLetters' => ['nullable', 'integer', 'min:1', 'max:20', 'gte:minLetters'],
        ]);

        $picked = $words->random($filters);

        $game = $request->user()->games()->create([
            'word' => $picked['word'],
            'hint' => $picked['hint'],
            'category' => $picked['category'],
            'guessed_letters' => [],
            'wrong_guesses' => 0,
            'status' => 'in_progress',
            'score' => 0,
        ]);

        return response()->json($this->state($game), 201);
    }

    /**
     * Show a single game belonging to the authenticated user.
     */
    public function show(Request $request, Game $game)
    {
        $this->authorizeOwner($request, $game);

        return response()->json($this->state($game, $game->status !== 'in_progress'));
    }

    /**
     * Delete a game belonging to the authenticated user.
     */
    public function destroy(Request $request, Game $game)
    {
        $this->authorizeOwner($request, $game);

        $game->delete();

        return response()->noContent();
    }

    /**
     * Submit a single-letter guess for an in-progress game.
     */
    public function makeGuess(Request $request, Game $game)
    {
        $this->authorizeOwner($request, $game);

        if ($game->status !== 'in_progress') {
            return response()->json([
                'message' => 'This game is already finished.',
            ], 422);
        }

        $validated = $request->validate([
            'letter' => ['required', 'string', 'size:1', 'regex:/^[A-Za-z]$/'],
            // The optional elapsed_seconds is the front-end's own clock reading.
            // The backend keeps no timer of its own; it just records what the
            // client reports so it can reward faster wins in the score.
            'elapsed_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $letter = strtoupper($validated['letter']);
        $guessed = $game->guessed_letters ?? [];

        if (in_array($letter, $guessed, true)) {
            return response()->json([
                'message' => 'You already guessed that letter.',
                'game' => $this->state($game),
            ], 422);
        }

        $guessed[] = $letter;
        $game->guessed_letters = $guessed;

        if (! str_contains($game->word, $letter)) {
            $game->wrong_guesses++;
        }

        // Determine the outcome.
        $allRevealed = collect(str_split($game->word))
            ->every(fn (string $char) => in_array($char, $guessed, true));

        if ($allRevealed) {
            $game->status = 'won';
        } elseif ($game->wrong_guesses >= self::MAX_WRONG) {
            $game->status = 'lost';
        }

        // When the round ends, lock in the front-end-reported time and the score.
        if ($game->status !== 'in_progress') {
            $game->elapsed_seconds = $request->input('elapsed_seconds', $game->elapsed_seconds);
            $game->score = $game->status === 'won' ? $this->computeScore($game) : 0;
        }

        $game->save();

        if ($game->status !== 'in_progress') {
            $this->updateLeaderboard($request->user(), $game);
        }

        return response()->json($this->state($game, $game->status !== 'in_progress'));
    }

    /**
     * Build the public-facing state for a game. The word is only revealed
     * once the game has finished, so an in-progress word can't be sniffed.
     */
    private function state(Game $game, bool $reveal = false): array
    {
        $guessed = $game->guessed_letters ?? [];

        $masked = collect(str_split($game->word))
            ->map(fn (string $char) => in_array($char, $guessed, true) ? $char : '_')
            ->implode(' ');

        $state = [
            'id' => $game->id,
            'masked_word' => $masked,
            'word_length' => strlen($game->word),
            'guessed_letters' => $guessed,
            'wrong_guesses' => $game->wrong_guesses,
            'max_wrong_guesses' => self::MAX_WRONG,
            'remaining_attempts' => max(0, self::MAX_WRONG - $game->wrong_guesses),
            'status' => $game->status,
            'score' => $game->score,
            'elapsed_seconds' => $game->elapsed_seconds,
            'hint' => $game->hint,
            'category' => $game->category,
            'created_at' => $game->created_at,
            'updated_at' => $game->updated_at,
        ];

        if ($reveal) {
            $state['word'] = $game->word;
        }

        return $state;
    }

    /**
     * Score awarded for a finished game.
     *
     *  - Base: 10 points per letter.
     *  - Near-miss multiplier: winning with only one guess to spare is clutch, so
     *    the base is multiplied by 1 + 0.15 per distinct correct letter uncovered
     *    (the more of a long word you pull off on the brink, the bigger the reward).
     *  - Penalty: 5 points per wrong guess.
     *  - Speed bonus: up to 120 points, shrinking by one for every second the
     *    front-end clock recorded — the less time you take, the higher the score.
     */
    private function computeScore(Game $game): int
    {
        $word = $game->word;
        $base = strlen($word) * 10;

        $multiplier = 1.0;
        if ($game->status === 'won' && $game->wrong_guesses === self::MAX_WRONG - 1) {
            $guessed = $game->guessed_letters ?? [];
            $distinctCorrect = collect(str_split($word))
                ->unique()
                ->filter(fn (string $char) => in_array($char, $guessed, true))
                ->count();
            $multiplier = 1.0 + ($distinctCorrect * 0.15);
        }

        $penalty = $game->wrong_guesses * 5;

        $elapsed = max(0, (int) ($game->elapsed_seconds ?? 0));
        $timeBonus = $elapsed > 0 ? max(0, 120 - $elapsed) : 0;

        return max(10, (int) round($base * $multiplier) - $penalty + $timeBonus);
    }

    /**
     * Update (or create) the player's leaderboard entry on game completion.
     */
    private function updateLeaderboard(User $user, Game $game): void
    {
        $entry = $user->leaderboardEntry()->firstOrCreate(
            ['user_id' => $user->id],
            ['total_score' => 0, 'games_won' => 0, 'games_played' => 0]
        );

        $entry->games_played++;

        if ($game->status === 'won') {
            $entry->games_won++;
            $entry->total_score += $game->score;
        }

        $entry->save();
    }

    /**
     * Ensure the game belongs to the authenticated user.
     */
    private function authorizeOwner(Request $request, Game $game): void
    {
        abort_unless($game->user_id === $request->user()->id, 403, 'This game does not belong to you.');
    }
}
