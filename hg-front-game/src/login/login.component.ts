import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
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
export class LoginComponent implements OnInit {
  loginForm: FormGroup;
  errorMessage: string | null = null;
  successMessage: string | null = null;

  constructor(private fb: FormBuilder, private apiService: ApiService) {
    this.loginForm = this.fb.group({
      username: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required]
    });
  }

  ngOnInit(): void {}

  onLogin(): void {
    if (!this.loginForm.valid) {
      return;
    }

    const loginCredentials = {
      email: this.loginForm.value.username,
      password: this.loginForm.value.password
    };

    this.apiService.login(loginCredentials).subscribe({
      next: (response) => {
        const token = response?.access_token;
        // Persist the token (shared with the Vue game on the same origin in
        // production) and hand off to the game, passing the token via the URL
        // so it also works cross-origin in local dev (4200 -> 8080).
        if (token) {
          localStorage.setItem(TOKEN_KEY, token);
        }
        if (response?.user?.id) {
          localStorage.setItem('user_id', String(response.user.id));
        }
        this.successMessage = 'Logged in successfully. Redirecting to the game…';
        setTimeout(() => {
          const separator = environment.gameUrl.includes('?') ? '&' : '?';
          window.location.href = token
            ? `${environment.gameUrl}${separator}token=${encodeURIComponent(token)}`
            : environment.gameUrl;
        }, 1200);
      },
      error: (error) => {
        this.errorMessage = error?.error?.message || 'An error occurred. Please try again.';
      },
    });
  }
}
