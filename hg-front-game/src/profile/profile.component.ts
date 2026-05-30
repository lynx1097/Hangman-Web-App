import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ApiService } from '../app/api.service';
import { environment } from '../environment/environment';
import { TOKEN_KEY } from '../app/auth.interceptor';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.scss'],
})
export class ProfileComponent implements OnInit {
  user: { id?: number; name?: string; email?: string } = {};
  stats: { total_score?: number; games_won?: number; games_played?: number } = {};
  errorMessage: string | null = null;
  loading = true;

  // Change-password panel
  showPasswordForm = false;
  passwordForm: FormGroup;
  passwordSaving = false;
  passwordMessage: string | null = null;
  passwordError: string | null = null;

  // Delete-account flow
  confirmingDelete = false;
  deleting = false;

  constructor(private fb: FormBuilder, private apiService: ApiService, private router: Router) {
    this.passwordForm = this.fb.group({
      current_password: ['', [Validators.required]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      password_confirmation: ['', [Validators.required]],
    });
  }

  get initial(): string {
    return (this.user.name || '?').charAt(0).toUpperCase();
  }

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

  togglePasswordForm(): void {
    this.showPasswordForm = !this.showPasswordForm;
    this.passwordMessage = null;
    this.passwordError = null;
  }

  changePassword(): void {
    if (this.passwordForm.invalid) {
      this.passwordForm.markAllAsTouched();
      return;
    }
    const { current_password, password, password_confirmation } = this.passwordForm.value;
    if (password !== password_confirmation) {
      this.passwordError = 'New passwords do not match.';
      return;
    }
    const userId = localStorage.getItem('user_id');
    if (!userId) { return; }

    this.passwordSaving = true;
    this.passwordError = null;
    this.passwordMessage = null;
    this.apiService.updatePassword(userId, { current_password, password, password_confirmation }).subscribe({
      next: () => {
        this.passwordSaving = false;
        this.passwordMessage = 'Password updated successfully.';
        this.passwordForm.reset();
      },
      error: (e) => {
        this.passwordSaving = false;
        this.passwordError = e?.error?.message || 'Could not update password.';
      },
    });
  }

  deleteAccount(): void {
    const userId = localStorage.getItem('user_id');
    if (!userId) { return; }
    this.deleting = true;
    this.apiService.deleteUser(userId).subscribe({
      next: () => this.clearAndRedirect(),
      error: () => {
        this.deleting = false;
        this.errorMessage = 'Could not delete account. Please try again.';
      },
    });
  }

  goLeaderboard(): void {
    this.router.navigate(['/leaderboard']);
  }

  playGame(): void {
    window.location.href = environment.gameUrl;
  }

  goHome(): void {
    this.router.navigate(['/home']);
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
