const { defineConfig } = require('@vue/cli-service');

module.exports = defineConfig({
  transpileDependencies: true,
  // Served under a sub-path on GitHub Pages (e.g. /Hangman-Web-App/game/).
  // CI sets VUE_APP_PUBLIC_PATH; local dev falls back to root.
  publicPath: process.env.VUE_APP_PUBLIC_PATH || '/',
});
