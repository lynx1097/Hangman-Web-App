import { Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ApiService } from '../app/api.service';
import { environment } from '../environment/environment';
import { TOKEN_KEY } from '../app/auth.interceptor';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnDestroy {
  loginForm: FormGroup;
  errorMessage: string | null = null;
  successMessage: string | null = null;
  loading = false;

  // Rotating tips shown on the cold-start loading screen.
  readonly tips: string[] = [
    'Tip: Start with common vowels — A, E and O appear in most words.',
    'Tip: Once you know a vowel, hunt for the consonants around it.',
    "Tip: You can play with your keyboard — just type a letter!",
    'Tip: Each correct guess keeps the figure off the gallows a little longer.',
    'Tip: Short words are often the trickiest — fewer letters to go on.',
  ];
  tipIndex = 0;
  private tipTimer: ReturnType<typeof setInterval> | null = null;

  constructor(private fb: FormBuilder, private apiService: ApiService, private router: Router) {
    this.loginForm = this.fb.group({
      username: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required]
    });
  }

  ngOnDestroy(): void {
    this.stopTips();
  }

  onLogin(): void {
    if (!this.loginForm.valid || this.loading) {
      return;
    }

    this.errorMessage = null;
    this.startLoading();

    const loginCredentials = {
      email: this.loginForm.value.username,
      password: this.loginForm.value.password
    };

    this.apiService.login(loginCredentials).subscribe({
      next: (response) => {
        const token = response?.access_token;
        if (token) {
          localStorage.setItem(TOKEN_KEY, token);
        }
        if (response?.user?.id) {
          localStorage.setItem('user_id', String(response.user.id));
        }
        this.stopTips();
        this.loading = false;
        this.successMessage = 'Logged in successfully. Redirecting to the game…';
        setTimeout(() => {
          const separator = environment.gameUrl.includes('?') ? '&' : '?';
          window.location.href = token
            ? `${environment.gameUrl}${separator}token=${encodeURIComponent(token)}`
            : environment.gameUrl;
        }, 1000);
      },
      error: (error) => {
        this.stopTips();
        this.loading = false;
        this.errorMessage = error?.error?.message || 'An error occurred. Please try again.';
      },
    });
  }

  goToSignup(): void {
    this.router.navigate(['/signup']);
  }

  private startLoading(): void {
    this.loading = true;
    this.tipIndex = 0;
    this.tipTimer = setInterval(() => {
      this.tipIndex = (this.tipIndex + 1) % this.tips.length;
    }, 3500);
  }

  private stopTips(): void {
    if (this.tipTimer) {
      clearInterval(this.tipTimer);
      this.tipTimer = null;
    }
  }
}
