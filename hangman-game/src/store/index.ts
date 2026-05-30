import axios from 'axios';
import { createStore } from 'vuex';

// The Laravel backend. It owns the word: the client only ever receives the
// masked word + hint, and every guess is checked server-side. Configured at
// build time (CI injects VUE_APP_API_BASE); dev falls back to localhost.
const API_BASE = process.env.VUE_APP_API_BASE || 'http://127.0.0.1:8000/api';

// Token key shared with the Angular login (it stores the Sanctum token here
// after a successful login before redirecting to this game).
const TOKEN_KEY = 'auth_token';

// Where to send unauthenticated players to log in (the Angular app).
export const LOGIN_URL = process.env.VUE_APP_LOGIN_URL || 'http://localhost:4200/#/login';

export type GameStatus = 'idle' | 'in_progress' | 'won' | 'lost';

export interface GameState {
  token: string | null;
  gameId: number | null;
  maskedWord: string;
  wordLength: number;
  hint: string | null;
  category: string | null;
  guessedLetters: string[];
  wrongGuesses: number;
  remainingAttempts: number;
  maxWrongGuesses: number;
  status: GameStatus;
  score: number;
  revealedWord: string | null;
  loading: boolean;
  error: string | null;
}

export type RootState = GameState;

/**
 * Resolve the auth token. Supports a handoff via `?token=...` in the URL
 * (e.g. the Angular login redirecting here), persisting it to localStorage
 * and cleaning the query string, then falls back to the stored token.
 */
function readToken(): string | null {
  try {
    const url = new URL(window.location.href);
    const fromQuery = url.searchParams.get('token');
    if (fromQuery) {
      localStorage.setItem(TOKEN_KEY, fromQuery);
      url.searchParams.delete('token');
      window.history.replaceState({}, document.title, url.pathname + url.search);
      return fromQuery;
    }
    return localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

function authHeaders(token: string | null) {
  return { Authorization: `Bearer ${token}`, Accept: 'application/json' };
}

/* eslint-disable @typescript-eslint/no-explicit-any */
function applyGameState(state: GameState, data: any): void {
  state.gameId = data.id;
  state.maskedWord = data.masked_word;
  state.wordLength = data.word_length;
  state.hint = data.hint ?? null;
  state.category = data.category ?? null;
  state.guessedLetters = data.guessed_letters ?? [];
  state.wrongGuesses = data.wrong_guesses;
  state.remainingAttempts = data.remaining_attempts;
  state.maxWrongGuesses = data.max_wrong_guesses;
  state.status = data.status;
  state.score = data.score;
  state.revealedWord = data.word ?? null;
}

function resolveError(error: any): string {
  if (error?.response?.status === 401) {
    return 'Your session has expired. Please log in again.';
  }
  return error?.response?.data?.message || 'Something went wrong. Please try again.';
}
/* eslint-enable @typescript-eslint/no-explicit-any */

const store = createStore<RootState>({
  state: (): GameState => ({
    token: readToken(),
    gameId: null,
    maskedWord: '',
    wordLength: 0,
    hint: null,
    category: null,
    guessedLetters: [],
    wrongGuesses: 0,
    remainingAttempts: 6,
    maxWrongGuesses: 6,
    status: 'idle',
    score: 0,
    revealedWord: null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.token,
    isPlaying: (state): boolean => state.status === 'in_progress',
    gameWon: (state): boolean => state.status === 'won',
    gameLost: (state): boolean => state.status === 'lost',
    isFinished: (state): boolean => state.status === 'won' || state.status === 'lost',
    hangmanImageIndex: (state): number => Math.min(state.wrongGuesses, state.maxWrongGuesses),
  },

  mutations: {
    setLoading(state, value: boolean) {
      state.loading = value;
    },
    setError(state, message: string | null) {
      state.error = message;
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setGame(state, data: any) {
      applyGameState(state, data);
      state.error = null;
    },
  },

  actions: {
    // Start a new game on the backend (optionally with category/length filters).
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    async startGame({ state, commit }, filters: Record<string, any> = {}) {
      if (!state.token) {
        commit('setError', 'Please log in to play.');
        return;
      }
      commit('setLoading', true);
      commit('setError', null);
      try {
        const response = await axios.post(`${API_BASE}/games`, filters, {
          headers: authHeaders(state.token),
        });
        commit('setGame', response.data);
      } catch (error) {
        commit('setError', resolveError(error));
      } finally {
        commit('setLoading', false);
      }
    },

    // Send a single-letter guess; the backend checks it against the real word.
    async guess({ state, commit }, letter: string) {
      const value = letter.toUpperCase();
      if (!state.gameId || state.status !== 'in_progress') return;
      if (state.guessedLetters.includes(value)) return;

      commit('setLoading', true);
      try {
        const response = await axios.post(
          `${API_BASE}/games/${state.gameId}/guesses`,
          { letter: value },
          { headers: authHeaders(state.token) }
        );
        commit('setGame', response.data);
      } catch (error) {
        commit('setError', resolveError(error));
      } finally {
        commit('setLoading', false);
      }
    },
  },
});

export default store;
