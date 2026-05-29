import { Component } from '@angular/core';
import { CommonModule, NgClass } from '@angular/common';


@Component({
  selector: 'game',
  templateUrl: './game.component.html',
  styleUrls: ['./game.component.css'],
  imports: [NgClass , CommonModule],
  standalone: true,
})
export class GameComponent {
  words: string[] = ['angular', 'typescript', 'component', 'service', 'directive'];
  selectedWord: string = '';
  displayWord: string = '';
  incorrectGuesses: string[] = [];
  remainingAttempts: number = 6;
  alphabet: string[] = 'abcdefghijklmnopqrstuvwxyz'.split('');

  // Start the game
  startGame(): void {
    const randomIndex = Math.floor(Math.random() * this.words.length);
    this.selectedWord = this.words[randomIndex];
    this.displayWord = '_'.repeat(this.selectedWord.length); // Initialize with underscores
    this.incorrectGuesses = [];
    this.remainingAttempts = 6;
  }

  // Handle letter guess
  guessLetter(letter: string): void {
    if (this.selectedWord.includes(letter)) {
      // Update display word
      let newDisplayWord = '';
      for (let i = 0; i < this.selectedWord.length; i++) {
        newDisplayWord += this.selectedWord[i] === letter ? letter : this.displayWord[i];
      }
      this.displayWord = newDisplayWord;
    } else {
      // Incorrect guess
      if (!this.incorrectGuesses.includes(letter)) {
        this.incorrectGuesses.push(letter);
        this.remainingAttempts--;
      }
    }
    this.checkGameStatus();
  }

  // Check for win or loss
  checkGameStatus(): void {
    if (this.displayWord === this.selectedWord) {
      alert('You Win!');
    } else if (this.remainingAttempts === 0) {
      alert('Game Over! The word was ' + this.selectedWord);
    }
  }

  // Initialize the game when the component is loaded
  ngOnInit(): void {
    this.startGame();
  }
}
