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
  --bg-1: #1e1b4b;
  --bg-2: #4c1d95;
  --surface: rgba(255, 255, 255, 0.07);
  --surface-strong: rgba(255, 255, 255, 0.12);
  --border: rgba(255, 255, 255, 0.16);
  --primary: #8b5cf6;
  --primary-dark: #6d28d9;
  --accent: #fbbf24;
  --danger: #fb7185;
  --success: #34d399;
  --text: #f5f3ff;
  --muted: #a5b4fc;

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
  background: radial-gradient(1200px 600px at 80% -10%, rgba(139, 92, 246, 0.35), transparent),
    linear-gradient(160deg, var(--bg-1), var(--bg-2));
  background-attachment: fixed;
}

#app {
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
