import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private userData = {
    username: 'Player1',
    name: 'John Doe',
    email: 'john.doe@example.com',
    picture: 'pfp.jpg',
    creditCard: '1234 5678 9012 3456',
    preferences: 'Likes puzzle games',
    highestScore: 100
  };

  getUserData() {
    return this.userData;
  }

  setHighestScore(score: number) {
    if (score > this.userData.highestScore) {
      this.userData.highestScore = score;
    }
  }
}
