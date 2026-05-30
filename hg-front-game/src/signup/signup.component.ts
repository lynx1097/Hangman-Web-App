import { Component, OnInit } from '@angular/core';
import {ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../app/api.service'; // Adjust the path as needed
import { CommonModule } from '@angular/common';
import { Router, RouterModule , Routes } from '@angular/router';

@Component({
  selector: 'app-signup',
  templateUrl: './signup.component.html',
  styleUrls: ['./signup.component.scss'],
  standalone: true, // Mark it as standalone
  imports: [ReactiveFormsModule,CommonModule],
})
export class SignupComponent implements OnInit {
  signupForm: FormGroup;
  errorMessage: string | null = null;
  successMessage: string | null = null;

  constructor(private fb: FormBuilder,private router : Router , private apiService: ApiService) {
    this.signupForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      // Mirror the backend rule: min 8, upper + lower + number + symbol.
      password: ['', [
        Validators.required,
        Validators.minLength(8),
        Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/),
      ]],
      confirmPassword: ['', [Validators.required]],
    }, { validators: this.passwordsMatch });
  }

  ngOnInit(): void {}

  // Custom validator to check if passwords match
  passwordsMatch(group: FormGroup): { [key: string]: boolean } | null {
    const password = group.get('password')?.value;
    const confirmPassword = group.get('confirmPassword')?.value;
    return password === confirmPassword ? null : { passwordsMismatch: true };
  }

  onSubmit(): void {
    if (this.signupForm.valid) {
      //const { name, email, password , password_confirmed } = this.signupForm.value;
      const registerBody = {
        name: this.signupForm.value.name,
        email: this.signupForm.value.email,
        password: this.signupForm.value.password,
        password_confirmation: this.signupForm.value.confirmPassword,
      };
      // Call the API service to register the user
      this.apiService.register(registerBody).subscribe({
        next: (response) => {
          console.log('User registered successfully:', response);
          this.successMessage = 'User registered successfully. You will be redirected to the login page in a few seconds.'; 
          setTimeout(() => {
            this.router.navigate(['/login']);
          }, 3000); 
        },
        error: (error) => {
            console.error('Error:', error);
            // Check for backend error message
            this.errorMessage = error.error.message || 'An error occurred. Please try again.';
          },
      });
    } else {
      console.error('Form is invalid');
    }
  }
}
