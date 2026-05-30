import { Component, OnDestroy } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../app/api.service';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';

@Component({
  selector: 'app-signup',
  templateUrl: './signup.component.html',
  styleUrls: ['./signup.component.scss'],
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule],
})
export class SignupComponent implements OnDestroy {
  signupForm: FormGroup;
  errorMessage: string | null = null;
  successMessage: string | null = null;
  loading = false;

  readonly tips: string[] = [
    'Tip: Start with common vowels — A, E and O appear in most words.',
    'Tip: Once you know a vowel, hunt for the consonants around it.',
    'Tip: You can play with your keyboard — just type a letter!',
    'Tip: Each correct guess keeps the figure off the gallows a little longer.',
  ];
  tipIndex = 0;
  private tipTimer: ReturnType<typeof setInterval> | null = null;

  constructor(private fb: FormBuilder, private router: Router, private apiService: ApiService) {
    this.signupForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      // Simple rule: at least 6 characters (mirrors the backend).
      password: ['', [Validators.required, Validators.minLength(6)]],
      confirmPassword: ['', [Validators.required]],
    }, { validators: this.passwordsMatch });
  }

  ngOnDestroy(): void {
    this.stopTips();
  }

  passwordsMatch(group: FormGroup): { [key: string]: boolean } | null {
    const password = group.get('password')?.value;
    const confirmPassword = group.get('confirmPassword')?.value;
    return password === confirmPassword ? null : { passwordsMismatch: true };
  }

  onSubmit(): void {
    if (!this.signupForm.valid || this.loading) {
      return;
    }

    this.errorMessage = null;
    this.startLoading();

    const registerBody = {
      name: this.signupForm.value.name,
      email: this.signupForm.value.email,
      password: this.signupForm.value.password,
      password_confirmation: this.signupForm.value.confirmPassword,
    };

    this.apiService.register(registerBody).subscribe({
      next: () => {
        this.stopTips();
        this.loading = false;
        this.successMessage = 'Account created! Redirecting you to the login page…';
        setTimeout(() => this.router.navigate(['/login']), 2500);
      },
      error: (error) => {
        this.stopTips();
        this.loading = false;
        this.errorMessage = error?.error?.message || 'An error occurred. Please try again.';
      },
    });
  }

  goToLogin(): void {
    this.router.navigate(['/login']);
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
