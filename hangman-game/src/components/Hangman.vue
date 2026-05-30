<template>
  <div class="hangman">
    <div class="topbar">
      <a class="btn btn-ghost home-btn" :href="homeUrl">🏠 Home</a>
    </div>

    <h1>Hangman</h1>

    <!-- Not logged in: the game needs a backend token -->
    <p v-if="!isAuthenticated" class="notice card">
      Please <a :href="loginUrl">log in</a> to play.
    </p>

    <template v-else>
      <p v-if="error" class="error">{{ error }}</p>

      <!-- Idle: offer to start -->
      <div v-if="status === 'idle' && !loading" class="start">
        <button class="btn" @click="newGame">Start Game</button>
      </div>

      <!-- A game is active or finished -->
      <div v-else-if="status !== 'idle'" class="board card">
        <!-- Animated SVG hangman: parts appear as wrong guesses mount -->
        <svg viewBox="0 0 160 200" class="gallows" :class="{ lost: gameLost }" aria-hidden="true">
          <g stroke="#fbbf24" stroke-width="6" stroke-linecap="round" fill="none">
            <line x1="20" y1="190" x2="110" y2="190" />
            <line x1="50" y1="190" x2="50" y2="20" />
            <line x1="50" y1="20" x2="120" y2="20" />
            <line x1="120" y1="20" x2="120" y2="40" />
          </g>
          <g stroke="#f5f3ff" stroke-width="5" stroke-linecap="round" fill="none">
            <circle class="part" :class="{ shown: wrongGuesses >= 1 }" cx="120" cy="56" r="16" />
            <line class="part" :class="{ shown: wrongGuesses >= 2 }" x1="120" y1="72" x2="120" y2="124" />
            <line class="part" :class="{ shown: wrongGuesses >= 3 }" x1="120" y1="86" x2="100" y2="108" />
            <line class="part" :class="{ shown: wrongGuesses >= 4 }" x1="120" y1="86" x2="140" y2="108" />
            <line class="part" :class="{ shown: wrongGuesses >= 5 }" x1="120" y1="124" x2="104" y2="152" />
            <line class="part" :class="{ shown: wrongGuesses >= 6 }" x1="120" y1="124" x2="136" y2="152" />
          </g>
        </svg>

        <p class="hint">
          <strong>Hint:</strong> {{ hint || 'No hint available' }}
          <span v-if="category" class="category">({{ category }})</span>
        </p>

        <p class="word">{{ maskedWord }}</p>

        <p class="status-line">
          <strong>Attempts left:</strong> {{ remainingAttempts }} / {{ maxWrongGuesses }}
          &nbsp;•&nbsp;
          <strong>⏱ Time:</strong>
          <span :class="{ 'near-miss': nearMiss }">{{ formattedTime }}</span>
        </p>

        <p v-if="nearMiss && isPlaying" class="near-miss-banner">
          🔥 One mistake left — finish now for a big near-miss bonus!
        </p>

        <p class="status-line">
          <strong>Guessed:</strong> {{ guessedLetters.join(', ') || '—' }}
        </p>

        <p class="kbd-hint">⌨️ Type a letter or tap the keys below.</p>

        <div class="keypad">
          <button
            v-for="letter in alphabet"
            :key="letter"
            class="key"
            :disabled="!isPlaying || guessedLetters.includes(letter) || busy"
            @click="guess(letter)"
          >
            {{ letter }}
          </button>
        </div>

        <!-- Result -->
        <div v-if="isFinished" class="result" :class="{ won: gameWon, lost: gameLost }">
          <h2>{{ gameWon ? 'You Won! 🎉' : 'Game Over' }}</h2>
          <p v-if="gameWon" class="final-score">Score: {{ score }}</p>
          <p class="final-time">Finished in {{ formattedTime }}</p>
          <p>The word was: <strong>{{ revealedWord }}</strong></p>
          <button class="btn btn-accent" @click="newGame">Play Again</button>
        </div>
      </div>
    </template>

    <!-- Cold-start loading overlay -->
    <div v-if="showOverlay" class="overlay">
      <div class="overlay-inner">
        <div class="spinner" aria-hidden="true"><span></span><span></span><span></span></div>
        <h3>Dealing you a word…</h3>
        <p class="tip">{{ tips[tipIndex] }}</p>
        <p class="honest">
          Heads up: our server sleeps on a free plan to keep costs down, so the
          very first request can take up to a minute to wake it. Hang tight! 💜
        </p>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useStore } from 'vuex';
import { RootState, LOGIN_URL, HOME_URL } from '@/store';

// Minimum gap between accepted keystrokes — gives the backend room to respond
// before we let the next guess through.
const GUESS_COOLDOWN_MS = 300;

const TIPS = [
  'Tip: Start with common vowels — A, E and O appear in most words.',
  'Tip: Once you know a vowel, hunt for the consonants around it.',
  'Tip: RSTLNE are the most common consonants and vowels in English.',
  'Tip: Each wrong guess adds a body part — choose carefully!',
  'Tip: Short words are often the trickiest — fewer letters to go on.',
];

export default defineComponent({
  name: 'Hangman',
  setup() {
    const store = useStore<RootState>();
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    const busy = ref(false);
    const tipIndex = ref(0);
    let tipTimer: ReturnType<typeof setInterval> | null = null;

    // ---- Front-end clock ----------------------------------------------------
    // The backend keeps no timer; we measure elapsed seconds here and report
    // them with each guess so the server can reward speed in the final score.
    const elapsed = ref(0);
    let startTime = 0;
    let clockTimer: ReturnType<typeof setInterval> | null = null;

    const stopClock = () => {
      if (clockTimer) {
        clearInterval(clockTimer);
        clockTimer = null;
      }
    };

    const startClock = () => {
      stopClock();
      elapsed.value = 0;
      startTime = Date.now();
      clockTimer = setInterval(() => {
        elapsed.value = Math.floor((Date.now() - startTime) / 1000);
      }, 1000);
    };

    const loading = computed(() => store.state.loading);
    const status = computed(() => store.state.status);
    const isPlaying = computed(() => store.getters.isPlaying);
    const isFinished = computed(() => store.getters.isFinished);
    const guessedLetters = computed(() => store.state.guessedLetters);

    // Full-screen overlay only for the initial spin-up (before any board shows).
    const showOverlay = computed(() => loading.value && status.value === 'idle');

    const remainingAttempts = computed(() => store.state.remainingAttempts);
    // "Near miss" = one wrong guess away from losing.
    const nearMiss = computed(() => remainingAttempts.value === 1);

    const formattedTime = computed(() => {
      const m = Math.floor(elapsed.value / 60);
      const s = elapsed.value % 60;
      return `${m}:${s.toString().padStart(2, '0')}`;
    });

    // Start/stop the clock as the game enters and leaves the "in_progress" state.
    watch(status, (next, prev) => {
      if (next === 'in_progress' && prev !== 'in_progress') {
        startClock();
      } else if (next !== 'in_progress') {
        stopClock();
      }
    });

    const newGame = () => store.dispatch('startGame');

    // One guess at a time, paced so the backend can keep up.
    const guess = async (letter: string) => {
      const value = letter.toUpperCase();
      if (busy.value || !isPlaying.value || loading.value) return;
      if (guessedLetters.value.includes(value)) return;
      busy.value = true;
      await store.dispatch('guess', { letter: value, elapsedSeconds: elapsed.value });
      setTimeout(() => { busy.value = false; }, GUESS_COOLDOWN_MS);
    };

    const onKeydown = (e: KeyboardEvent) => {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      const key = e.key;
      if (key.length === 1 && /[a-zA-Z]/.test(key)) {
        guess(key);
      }
    };

    onMounted(() => {
      window.addEventListener('keydown', onKeydown);
      if (store.getters.isAuthenticated) {
        store.dispatch('startGame');
      }
      tipTimer = setInterval(() => {
        tipIndex.value = (tipIndex.value + 1) % TIPS.length;
      }, 3500);
    });

    onUnmounted(() => {
      window.removeEventListener('keydown', onKeydown);
      if (tipTimer) clearInterval(tipTimer);
      stopClock();
    });

    return {
      alphabet,
      tips: TIPS,
      tipIndex,
      loginUrl: LOGIN_URL,
      homeUrl: HOME_URL,
      newGame,
      guess,
      busy,
      showOverlay,
      elapsed,
      formattedTime,
      nearMiss,
      // state
      maskedWord: computed(() => store.state.maskedWord),
      hint: computed(() => store.state.hint),
      category: computed(() => store.state.category),
      guessedLetters,
      wrongGuesses: computed(() => store.state.wrongGuesses),
      remainingAttempts: computed(() => store.state.remainingAttempts),
      maxWrongGuesses: computed(() => store.state.maxWrongGuesses),
      status,
      score: computed(() => store.state.score),
      revealedWord: computed(() => store.state.revealedWord),
      loading,
      error: computed(() => store.state.error),
      // getters
      isAuthenticated: computed(() => store.getters.isAuthenticated),
      isPlaying,
      gameWon: computed(() => store.getters.gameWon),
      gameLost: computed(() => store.getters.gameLost),
      isFinished,
    };
  },
});
</script>

<style scoped>
.hangman {
  max-width: 640px;
  margin: 0 auto;
}

h1 {
  font-size: clamp(2.4rem, 7vw, 3.4rem);
  margin-bottom: 18px;
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  backdrop-filter: blur(12px);
}

.notice {
  font-size: 1.1rem;
  padding: 26px;
}

.error {
  color: var(--danger);
  font-size: 1.05rem;
}

.board {
  padding: 24px 22px 30px;
}

.gallows {
  width: 200px;
  height: auto;
}

.gallows.lost .part.shown {
  stroke: var(--danger);
}

.part {
  opacity: 0;
  transform: scale(0.85);
  transform-origin: 120px 60px;
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.part.shown {
  opacity: 1;
  transform: scale(1);
}

.hint {
  font-size: 1.05rem;
  margin: 10px 0 4px;
}

.category {
  color: var(--muted);
  font-style: italic;
}

.word {
  font-size: 2.4rem;
  letter-spacing: 0.4rem;
  font-family: monospace;
  margin: 12px 0;
}

.status-line {
  color: var(--muted);
  font-size: 0.95rem;
}

.kbd-hint {
  color: var(--accent);
  font-size: 0.9rem;
  margin: 6px 0 14px;
}

.keypad {
  display: grid;
  grid-template-columns: repeat(13, 1fr);
  gap: 6px;
  margin: 8px 0;
}

.key {
  padding: 12px 0;
  font-size: 1.05rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.1s ease, opacity 0.15s ease;
}

.key:hover:not(:disabled) {
  transform: translateY(-2px);
}

.key:disabled {
  background: rgba(255, 255, 255, 0.12);
  color: rgba(245, 243, 255, 0.35);
  cursor: not-allowed;
}

.btn {
  font-family: var(--font-body);
  font-weight: 600;
  border: none;
  border-radius: 12px;
  padding: 12px 28px;
  font-size: 1rem;
  color: #fff;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  box-shadow: 0 8px 20px rgba(109, 40, 217, 0.4);
  transition: transform 0.15s ease;
}

.btn:hover {
  transform: translateY(-2px);
}

.btn-accent {
  background: linear-gradient(135deg, #f59e0b, var(--accent));
  color: #3b2606;
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  box-shadow: none;
}

.topbar {
  display: flex;
  justify-content: flex-start;
  margin-bottom: 8px;
}

.home-btn {
  display: inline-block;
  text-decoration: none;
  padding: 9px 18px;
  font-size: 0.95rem;
}

.result {
  margin-top: 22px;
  padding: 22px;
  border-radius: 14px;
}

.result.won {
  background: rgba(52, 211, 153, 0.15);
  border: 1px solid var(--success);
}

.result.lost {
  background: rgba(251, 113, 133, 0.15);
  border: 1px solid var(--danger);
}

/* Overlay */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 12, 41, 0.82);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: 20px;
}

.overlay-inner {
  max-width: 440px;
}

.overlay-inner h3 {
  font-size: 1.6rem;
  margin: 18px 0 10px;
}

.tip {
  color: var(--accent);
  font-size: 1.05rem;
  min-height: 2.6em;
  margin: 0 0 18px;
}

.honest {
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.5;
}

.spinner {
  display: inline-flex;
  gap: 8px;
}

.spinner span {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--primary);
  animation: bounce 1.2s ease-in-out infinite;
}

.spinner span:nth-child(2) { animation-delay: 0.15s; background: var(--accent); }
.spinner span:nth-child(3) { animation-delay: 0.3s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0.5); opacity: 0.5; }
  40% { transform: scale(1); opacity: 1; }
}

@media (max-width: 600px) {
  .hangman {
    padding: 0 4px;
  }

  .board {
    padding: 20px 14px 24px;
  }

  .gallows {
    width: 150px;
  }

  .word {
    font-size: 1.8rem;
    letter-spacing: 0.25rem;
  }

  /* A 13-wide row is too cramped on phones — drop to 7 columns. */
  .keypad {
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
  }

  .key {
    padding: 14px 0;
  }

  .status-line {
    font-size: 0.85rem;
  }
}

/* Timer + near-miss accents (appended) */
.status-line .near-miss {
  color: var(--danger);
  font-weight: 700;
}

.near-miss-banner {
  color: var(--accent);
  font-weight: 700;
  margin: 8px 0;
  animation: pulse 1.2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.55; }
}

.final-score {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--accent);
  margin: 6px 0;
}

.final-time {
  color: var(--muted);
  margin: 4px 0;
}
</style>
