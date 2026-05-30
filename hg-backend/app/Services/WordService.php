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
     * @param  array{category?: ?string, minLetters?: ?int, maxLetters?: ?int}  $filters
     * @return array{word: string, hint: ?string, category: ?string, num_letters: int, num_syllables: ?int}
     */
    public function random(array $filters = []): array
    {
        $filters = array_filter([
            'category' => $filters['category'] ?? null,
            'minLetters' => $filters['minLetters'] ?? null,
            'maxLetters' => $filters['maxLetters'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $word = empty($filters)
                ? $this->fetchRandom()
                : $this->fetchFiltered($filters);

            if ($word && ! empty($word['word'])) {
                return $this->normalize($word);
            }
        } catch (\Throwable $e) {
            Log::warning('Word Game DB unavailable, using local fallback: '.$e->getMessage());
        }

        return $this->fallback($filters);
    }

    /**
     * GET /api/v2/words/random — a single random word object.
     */
    private function fetchRandom(): ?array
    {
        $response = Http::acceptJson()->timeout(5)->get(self::BASE_URL.'/words/random');

        return $response->successful() ? $response->json() : null;
    }

    /**
     * GET /api/v2/words?category=&minLetters=&maxLetters= — pick a random
     * entry from the filtered, paginated result set.
     */
    private function fetchFiltered(array $filters): ?array
    {
        $response = Http::acceptJson()->timeout(5)->get(self::BASE_URL.'/words', $filters + ['limit' => 50]);

        if (! $response->successful()) {
            return null;
        }

        $words = $response->json('words', []);

        return empty($words) ? null : Arr::random($words);
    }

    /**
     * Pick from the local config/words.php list, honouring the same filters.
     */
    private function fallback(array $filters): array
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

        return $this->normalize($words->random());
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
