<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provides random words (with hints) for new hangman games.
 *
 * Primary source is the free, read-only Word Game DB API (v2). If the API is
 * unreachable, times out, or returns nothing usable, it falls back to the
 * local list in config/words.php, which is kept in the same shape as the API
 * so the swap is seamless and the player never notices an outage.
 *
 * @see https://www.wordgamedb.com/
 */
class WordService
{
    private const BASE_URL = 'https://www.wordgamedb.com/api/v2';

    /**
     * Fetch a random word, optionally constrained by category / length.
     *
     * Anti-repetition: any word in $exclude (typically the words a player has
     * already been served) is skipped so games don't repeat. The mechanism is
     * "serve every available word once, then recycle": once the source can no
     * longer offer a fresh word the exclusion is dropped and words may repeat
     * again — so a player can always start a game.
     *
     * @param  array{category?: ?string, minLetters?: ?int, maxLetters?: ?int}  $filters
     * @param  list<string>  $exclude  Words the player has already seen (any case).
     * @return array{word: string, hint: ?string, category: ?string, num_letters: int, num_syllables: ?int}
     */
    public function random(array $filters = [], array $exclude = []): array
    {
        $filters = array_filter([
            'category' => $filters['category'] ?? null,
            'minLetters' => $filters['minLetters'] ?? null,
            'maxLetters' => $filters['maxLetters'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $exclude = array_map(fn ($w) => strtoupper((string) $w), $exclude);

        try {
            $word = empty($filters)
                ? $this->fetchRandom($exclude)
                : $this->fetchFiltered($filters, $exclude);

            if ($word && ! empty($word['word'])) {
                return $this->normalize($word);
            }
        } catch (\Throwable $e) {
            Log::warning('Word Game DB unavailable, using local fallback: '.$e->getMessage());
        }

        return $this->fallback($filters, $exclude);
    }

    /**
     * GET /api/v2/words/random — a single random word object.
     *
     * When excluding already-seen words we retry a few times to land on a
     * fresh one; if every attempt is a repeat (pool likely exhausted) we
     * accept the last result rather than fail to start a game.
     */
    private function fetchRandom(array $exclude = []): ?array
    {
        $attempts = empty($exclude) ? 1 : 8;
        $last = null;

        for ($i = 0; $i < $attempts; $i++) {
            $response = Http::acceptJson()->timeout(5)->get(self::BASE_URL.'/words/random');

            if (! $response->successful()) {
                return null;
            }

            $word = $response->json();
            $last = $word;

            if ($word && ! $this->isExcluded($word, $exclude)) {
                return $word;
            }
        }

        return $last;
    }

    /**
     * GET /api/v2/words?category=&minLetters=&maxLetters= — pick a random
     * entry from the filtered, paginated result set, preferring unseen words.
     */
    private function fetchFiltered(array $filters, array $exclude = []): ?array
    {
        $response = Http::acceptJson()->timeout(5)->get(self::BASE_URL.'/words', $filters + ['limit' => 50]);

        if (! $response->successful()) {
            return null;
        }

        $words = $response->json('words', []);

        if (empty($words)) {
            return null;
        }

        if (! empty($exclude)) {
            $fresh = array_values(array_filter($words, fn ($w) => ! $this->isExcluded($w, $exclude)));
            if (! empty($fresh)) {
                return Arr::random($fresh);
            }
        }

        return Arr::random($words);
    }

    /**
     * Pick from the local config/words.php list, honouring the same filters
     * and preferring words the player hasn't seen yet.
     */
    private function fallback(array $filters, array $exclude = []): array
    {
        $words = collect(config('words', []));

        if (! empty($filters['category'])) {
            $words = $words->where('category', $filters['category']);
        }
        if (! empty($filters['minLetters'])) {
            $words = $words->filter(fn ($w) => ($w['numLetters'] ?? strlen($w['word'])) >= $filters['minLetters']);
        }
        if (! empty($filters['maxLetters'])) {
            $words = $words->filter(fn ($w) => ($w['numLetters'] ?? strlen($w['word'])) <= $filters['maxLetters']);
        }
        if ($words->isEmpty()) {
            $words = collect(config('words', []));
        }

        if (! empty($exclude)) {
            $fresh = $words->reject(fn ($w) => $this->isExcluded($w, $exclude));
            if ($fresh->isNotEmpty()) {
                $words = $fresh;
            }
        }

        return $this->normalize($words->random());
    }

    /**
     * Whether a word object's value is in the (upper-cased) exclude list.
     *
     * @param  array<string, mixed>  $word
     * @param  list<string>  $exclude
     */
    private function isExcluded(array $word, array $exclude): bool
    {
        return in_array(strtoupper((string) ($word['word'] ?? '')), $exclude, true);
    }

    /**
     * Normalise an API/fallback word object into our internal shape.
     * The word is upper-cased so masking and guess comparison stay consistent.
     *
     * @param  array<string, mixed>  $word
     * @return array{word: string, hint: ?string, category: ?string, num_letters: int, num_syllables: ?int}
     */
    private function normalize(array $word): array
    {
        $value = strtoupper((string) $word['word']);

        return [
            'word' => $value,
            'hint' => $word['hint'] ?? null,
            'category' => $word['category'] ?? null,
            'num_letters' => (int) ($word['numLetters'] ?? strlen($value)),
            'num_syllables' => isset($word['numSyllables']) ? (int) $word['numSyllables'] : null,
        ];
    }
}
