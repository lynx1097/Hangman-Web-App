import axios from 'axios';
import { createStore, Store } from 'vuex';
import { InjectionKey } from 'vue';
// Define the state interface
export interface GameState {
  word: string;
  hint: string;
  guessedLetters: string[];
  incorrectGuesses: number;
  maxIncorrectGuesses: number;
  score: number;
  wordList: string[];
}

// Define the root state interface
export interface RootState {
  game: GameState;
}

// Create the Vuex store
const store = createStore<RootState>({
  state: {
    game: {
      word: '',
      hint: '',
      guessedLetters: [],
      incorrectGuesses: 0,
      maxIncorrectGuesses: 6,
      score: 0,
      wordList: [],
    },
  },
  getters: {
    wordDisplay(state): string {
      return state.game.word
        .split('')
        .map((letter) =>
          state.game.guessedLetters.includes(letter) ? letter : '_'
        )
        .join(' ');
    },
    gameOver(state): boolean {
      return state.game.incorrectGuesses >= state.game.maxIncorrectGuesses;
    },
    gameWon(state): boolean {
      return state.game.word.split('').every((letter) => state.game.guessedLetters.includes(letter));
    },
    hangmanImage(state): string {
      // Define the base path for our hangman images in the public directory
      const PUBLIC_IMAGES_PATH = '/images/hangman';  // Assuming images are in public/images/hangman
    
      // Define our image mapping with complete filenames
      const hangmanImages = [
        'hangman.png',
        'head.png',
        'head-body.png',
        'head-body-arms.png',
        'head-body-arms-legs.png',
        'head-body-arms-legs-hat.png',
        'head-body-arms-legs-hat-rope.png'
        
      ];
    
      // Combine the public path with the image filename
      // The index will be determined by your game state
      return `${PUBLIC_IMAGES_PATH}/${hangmanImages[state.game.incorrectGuesses]}`;
    },
  },
  mutations: {
    guessLetter(state, letter: string) {
      if (!state.game.guessedLetters.includes(letter)) {
        state.game.guessedLetters.push(letter);
        if (!state.game.word.includes(letter)) {
          state.game.incorrectGuesses++;
        }
      }
    },
    setWordAndHint(state, payload: { word: string; hint: string }) {
      state.game.word = payload.word.toUpperCase(); // Uppercase for uniformity
      state.game.hint = payload.hint;
      state.game.guessedLetters = [];
      state.game.incorrectGuesses = 0;
    },
    resetGame(state) {
      const randomWord =
        state.game.wordList[
          Math.floor(Math.random() * state.game.wordList.length)
        ];
      state.game.word = randomWord;
      state.game.guessedLetters = [];
      state.game.incorrectGuesses = 0;
    },
    increaseScore(state) {
      state.game.score++;
    },
  },
  actions: {
    async fetchWordAndHint({ commit }) {
      try {
        const response = await axios.get('https://www.wordgamedb.com/api/v1/words/random'); // Replace with your API URL
        const wordData = response.data;
        const { _id , word , category , numLetters , numSyllables , __v , hint } = response.data; // Adjust based on your API response structure
        commit('setWordAndHint', {
          word: wordData.word,
          hint: wordData.hint || 'No hint available',
        });
      } catch (error) {
        console.error('Error fetching word and hint:', error);
      }
    },
    makeGuess({ commit }, letter: string) {
      commit('guessLetter', letter);
    },
    resetGame({ commit }) {
      commit('resetGame');
    },
    increaseScore({ commit }) {
      commit('increaseScore');
    },
  },
});

export default store;
