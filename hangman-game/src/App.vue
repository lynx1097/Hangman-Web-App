<template>
  <Hangman />
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import Hangman from "./components/Hangman.vue";

@Options({
  components: {
    Hangman,
  },
})
export default class App extends Vue {}
</script>

<style>
/* Shared Hangman theme — kept in sync with the Angular app (hg-front-game). */
:root {
  --bg-1: #c8d4f9;
  --bg-2: #1b1442;
  --surface: rgba(255, 255, 255, 0.09);
  --surface-strong: rgba(255, 255, 255, 0.15);
  --border: rgba(255, 255, 255, 0.2);
  --primary: #818cf8;
  --primary-dark: #4f46e5;
  --accent: #fcd34d;
  --danger: #fb7185;
  --success: #34d399;
  /* Lighter, higher-contrast reading colours. */
  --text: #ffffff;
  --muted: #f0f2fc;

  --font-display: "Playwrite NZ Basic", cursive;
  --font-body: "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;

  --radius: 16px;
  --shadow: 0 18px 50px rgba(0, 0, 0, 0.45);
}

* {
  box-sizing: border-box;
}

html,
body {
  margin: 0;
  padding: 0;
  min-height: 100%;
}

body {
  font-family: var(--font-body);
  color: var(--text);
  background: radial-gradient(1200px 600px at 80% -10%, rgba(129, 140, 248, 0.3), transparent),
    radial-gradient(900px 500px at 0% 110%, rgba(252, 211, 77, 0.08), transparent),
    linear-gradient(160deg, var(--bg-1), var(--bg-2));
  background-attachment: fixed;
}

/* Slowly drifting glow — an ambient, living backdrop. */
body::before {
  content: "";
  position: fixed;
  inset: -50%;
  z-index: -1;
  background:
    radial-gradient(circle at 30% 30%, rgba(129, 140, 248, 0.18), transparent 40%),
    radial-gradient(circle at 70% 65%, rgba(252, 211, 77, 0.10), transparent 45%);
  animation: drift 24s ease-in-out infinite alternate;
}

/* Faint moving dot texture layered over the gradient. */
body::after {
  content: "";
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
  background-size: 28px 28px;
  animation: texture 32s linear infinite;
}

@keyframes drift {
  0% { transform: translate(0, 0) rotate(0deg); }
  100% { transform: translate(4%, 3%) rotate(8deg); }
}

@keyframes texture {
  from { background-position: 0 0; }
  to { background-position: 28px 56px; }
}

@media (prefers-reduced-motion: reduce) {
  body::before,
  body::after {
    animation: none;
  }
}

#app {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 32px 16px 56px;
}

h1,
h2,
h3 {
  font-family: var(--font-display);
  font-weight: 400;
}
</style>
