<template>
  <div>
    <h1 v-if="!gameOver && !gameWon">Hangman Game</h1>
    
    <!-- Game Over alert -->
    <div v-if="gameOver || gameWon" class="game-over">
      <h2>{{ gameWon ? 'You Won!' : 'Game Over!' }}</h2>
      <p>Your score: {{ score }}</p>
      <button @click="resetGame">Play Again</button>
    </div>
    
    <div v-else>
      <!-- Display the hint -->
      <p><strong>Hint:</strong> {{ hint }}</p>
      
      <!-- Word Display -->
      <p><strong>Word:</strong> {{ wordDisplay }}</p>

      <!-- Display the hangman image based on incorrect guesses -->
      <img :src="`/images/hangman/${hangmanImage}.png`" alt="Hangman" />
      
      <!-- Display guessed letters -->
      <p><strong>Guessed Letters:</strong> {{ guessedLetters.join(', ') }}</p>
      <p><strong>Incorrect Guesses:</strong> {{ incorrectGuesses }} / {{ maxIncorrectGuesses }}</p>

      <!-- Keypad -->
      <div class="keypad">
        <div v-for="letter in alphabet" :key="letter">
          <button 
            :disabled="guessedLetters.includes(letter)" 
            @click="makeGuess(letter)">
            {{ letter }}
          </button>
        </div>
      </div>

      <p><strong>Score:</strong> {{ score }}</p>
      <button @click="resetGame">Reset Game</button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import { useStore } from 'vuex';
import { RootState } from '@/store';

export default defineComponent({
  name: 'Hangman',
  setup() {
    const store = useStore<RootState>();

    // Define the alphabet for the keypad
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    // Computed properties for hint, word display, hangman image, and guessed letters
    const hint = computed(() => store.state.game.hint);
    const wordDisplay = computed(() => store.getters.wordDisplay);
    const hangmanImage = computed(() => store.state.game.incorrectGuesses);
    const guessedLetters = computed(() => store.state.game.guessedLetters);

    // Make a guess and dispatch to Vuex store
    const makeGuess = (letter: string) => {
      if (letter && !guessedLetters.value.includes(letter.toUpperCase())) {
        store.dispatch('makeGuess', letter.toUpperCase());
      }
    };

    // Reset game and fetch a new word and hint
    const resetGame = async () => {
      await store.dispatch('fetchWordAndHint');
    };

    // Return all the necessary properties for the template
    return {
      alphabet,
      hint,
      wordDisplay,
      hangmanImage,
      guessedLetters,
      makeGuess,
      resetGame,
    };
  },
  data() {
    return {
      currentGuess: ''
    };
  },
  computed: {
    store() {
      return useStore<RootState>();
    },
    word(): string {
      return this.store.state.game.word;
    },
    wordDisplay(): string {
      return this.store.getters.wordDisplay;
    },
    guessedLetters(): string[] {
      return this.store.state.game.guessedLetters;
    },
    incorrectGuesses(): number {
      return this.store.state.game.incorrectGuesses;
    },
    maxIncorrectGuesses(): number {
      return this.store.state.game.maxIncorrectGuesses;
    },
    gameOver(): boolean {
      return this.store.getters.gameOver;
    },
    gameWon(): boolean {
      return this.store.getters.gameWon;
    },
    hangmanImage(): string {
      return this.store.getters.hangmanImage;
    },
    score(): number {
      return this.store.state.game.score;
    }
  },
  methods: {
    guessLetter() {
      if (this.currentGuess && !this.gameOver && !this.gameWon) {
        this.store.dispatch('makeGuess', this.currentGuess.toUpperCase());
        this.currentGuess = '';
      }
    },
    resetGame() {
      this.store.dispatch('resetGame');
      this.store.dispatch('increaseScore');
      this.currentGuess = '';
    }
  }
});
</script>

<style scoped>
/* Keypad styling */
.keypad {
  display: grid;
  grid-template-columns: repeat(20, 1fr); /* 7 columns for alphabet (A-Z) */
  gap: 5px;
  margin-top: 20px;
}

.keypad button {
  padding: 12px;
  font-size: 1.2rem;
  background-color: #007bff;
  color: white;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  transition: background-color 0.3s;
}

.keypad button:disabled {
  background-color: #dcdcdc;
  cursor: not-allowed;
}

.keypad button:hover:not(:disabled) {
  background-color: #0056b3;
}

button {
  padding: 10px;
  background-color: #007bff;
  color: white;
  border: none;
  cursor: pointer;
  border-radius: 5px;
}

button:hover {
  background-color: #0056b3;
}

.game-over {
  text-align: center;
  padding: 20px;
  background-color: #ff6f61;
  color: white;
  border-radius: 10px;
}

.game-over h2 {
  font-size: 2rem;
}

.game-over button {
  margin-top: 20px;
  background-color: #007bff;
  color: white;
  border-radius: 5px;
}
</style>
