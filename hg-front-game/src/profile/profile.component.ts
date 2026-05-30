import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ApiService } from '../app/api.service';
import { TOKEN_KEY } from '../app/auth.interceptor';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.scss']
})
export class ProfileComponent implements OnInit {
  user: { id?: number; name?: string; email?: string } = {};
  stats: { total_score?: number; games_won?: number; games_played?: number } = {};
  errorMessage: string | null = null;
  loading = true;

  constructor(private apiService: ApiService, private router: Router) {}

  ngOnInit(): void {
    const userId = localStorage.getItem('user_id');
    if (!userId) {
      this.router.navigate(['/login']);
      return;
    }

    this.apiService.getUser(userId).subscribe({
      next: (user) => { this.user = user; },
      error: () => { this.errorMessage = 'Could not load your profile.'; },
    });

    this.apiService.getUserLeaderboard(userId).subscribe({
      next: (stats) => { this.stats = stats; this.loading = false; },
      error: () => { this.loading = false; },
    });
  }

  logout(): void {
    this.apiService.logout().subscribe({
      next: () => this.clearAndRedirect(),
      error: () => this.clearAndRedirect(),
    });
  }

  private clearAndRedirect(): void {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem('user_id');
    this.router.navigate(['/login']);
  }
}
