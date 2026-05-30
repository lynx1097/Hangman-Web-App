<template>
  <div class="hangman">
    <h1>Hangman</h1>

    <!-- Not logged in: the game needs a backend token -->
    <p v-if="!isAuthenticated" class="notice">
      Please <a :href="loginUrl">log in</a> to play.
    </p>

    <template v-else>
      <p v-if="error" class="error">{{ error }}</p>

      <!-- Idle: offer to start -->
      <div v-if="status === 'idle'" class="start">
        <button :disabled="loading" @click="newGame">
          {{ loading ? 'Starting…' : 'Start Game' }}
        </button>
      </div>

      <!-- A game is active or finished -->
      <div v-else class="board">
        <img :src="`${baseUrl}images/hangman/${hangmanImageIndex}.png`" alt="Hangman" />

        <p class="hint">
          <strong>Hint:</strong> {{ hint || 'No hint available' }}
          <span v-if="category" class="category">({{ category }})</span>
        </p>

        <p class="word">{{ maskedWord }}</p>

        <p class="status-line">
          <strong>Attempts left:</strong> {{ remainingAttempts }} / {{ maxWrongGuesses }}
          &nbsp;•&nbsp;
          <strong>Guessed:</strong> {{ guessedLetters.join(', ') || '—' }}
        </p>

        <div class="keypad">
          <button
            v-for="letter in alphabet"
            :key="letter"
            :disabled="!isPlaying || guessedLetters.includes(letter) || loading"
            @click="guess(letter)"
          >
            {{ letter }}
          </button>
        </div>

        <!-- Result -->
        <div v-if="isFinished" class="result" :class="{ won: gameWon, lost: gameLost }">
          <h2>{{ gameWon ? 'You Won! 🎉' : 'Game Over' }}</h2>
          <p v-if="gameWon">Score: {{ score }}</p>
          <p>The word was: <strong>{{ revealedWord }}</strong></p>
          <button :disabled="loading" @click="newGame">Play Again</button>
        </div>
      </div>
    </template>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { RootState, LOGIN_URL } from '@/store';

export default defineComponent({
  name: 'Hangman',
  setup() {
    const store = useStore<RootState>();
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    // Public path the app is served under (root in dev, /…/game/ on Pages).
    const baseUrl = process.env.BASE_URL;

    const newGame = () => store.dispatch('startGame');
    const guess = (letter: string) => store.dispatch('guess', letter);

    // Auto-start a game for an authenticated player on load.
    onMounted(() => {
      if (store.getters.isAuthenticated) {
        store.dispatch('startGame');
      }
    });

    return {
      alphabet,
      baseUrl,
      loginUrl: LOGIN_URL,
      newGame,
      guess,
      // state
      maskedWord: computed(() => store.state.maskedWord),
      hint: computed(() => store.state.hint),
      category: computed(() => store.state.category),
      guessedLetters: computed(() => store.state.guessedLetters),
      remainingAttempts: computed(() => store.state.remainingAttempts),
      maxWrongGuesses: computed(() => store.state.maxWrongGuesses),
      status: computed(() => store.state.status),
      score: computed(() => store.state.score),
      revealedWord: computed(() => store.state.revealedWord),
      loading: computed(() => store.state.loading),
      error: computed(() => store.state.error),
      // getters
      isAuthenticated: computed(() => store.getters.isAuthenticated),
      isPlaying: computed(() => store.getters.isPlaying),
      gameWon: computed(() => store.getters.gameWon),
      gameLost: computed(() => store.getters.gameLost),
      isFinished: computed(() => store.getters.isFinished),
      hangmanImageIndex: computed(() => store.getters.hangmanImageIndex),
    };
  },
});
</script>

<style scoped>
.hangman {
  max-width: 640px;
  margin: 0 auto;
}

.notice,
.error {
  font-size: 1.1rem;
}

.error {
  color: #b00020;
}

img {
  max-height: 240px;
}

.word {
  font-size: 2rem;
  letter-spacing: 0.3rem;
  font-family: monospace;
}

.category {
  color: #6c757d;
  font-style: italic;
}

.keypad {
  display: grid;
  grid-template-columns: repeat(13, 1fr);
  gap: 5px;
  margin: 20px 0;
}

.keypad button {
  padding: 12px 0;
  font-size: 1.1rem;
  background-color: #007bff;
  color: white;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.keypad button:disabled {
  background-color: #dcdcdc;
  cursor: not-allowed;
}

.keypad button:hover:not(:disabled) {
  background-color: #0056b3;
}

button {
  padding: 10px 16px;
  background-color: #007bff;
  color: white;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  font-size: 1rem;
}

button:hover:not(:disabled) {
  background-color: #0056b3;
}

.result {
  margin-top: 20px;
  padding: 20px;
  border-radius: 10px;
  color: white;
}

.result.won {
  background-color: #2e7d32;
}

.result.lost {
  background-color: #ff6f61;
}
</style>
