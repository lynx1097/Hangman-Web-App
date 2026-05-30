import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { TOKEN_KEY } from '../app/auth.interceptor';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.scss']
})
export class HomeComponent {
  constructor(private router: Router) {}

  /** Whether a player is currently signed in (drives which buttons show). */
  get isLoggedIn(): boolean {
    return !!localStorage.getItem(TOKEN_KEY);
  }

  navigateToLogin(): void {
    this.router.navigate(['/login']);
  }

  navigateToSignup(): void {
    this.router.navigate(['/signup']);
  }

  navigateToLeaderboard(): void {
    this.router.navigate(['/leaderboard']);
  }

  navigateToProfile(): void {
    this.router.navigate(['/profile']);
  }
}
