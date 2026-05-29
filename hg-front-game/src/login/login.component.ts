import { Component ,OnInit} from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ApiService } from '../app/api.service';

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

  constructor(private fb: FormBuilder,private apiService: ApiService) {
    this.loginForm = this.fb.group({
      username: ['', Validators.required],
      password: ['', Validators.required]
    });
  }
  ngOnInit(): void {}
  setCookie(name: string, value: boolean) {
    document.cookie = `${name}=${value}`;
  }
  getCookie(name: string): string | null {
    const nameEQ = `${name}=`;
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
      let c = cookies[i];
      while (c.charAt(0) === ' ') c = c.substring(1, c.length);  // Remove leading spaces
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);  // Return cookie value
    }
    return null;  // Cookie not found
  }
  deleteCookie(name: string) {
    document.cookie = `${name}=false`;
  }
  
  
  
  onLogin(): void {
    if (this.loginForm.valid) {
      const loginCredentials = {
        email: this.loginForm.value.username,
        password: this.loginForm.value.password
      };
      this.apiService.login(loginCredentials).subscribe({
        next: (response) => {
          console.log('User logged in successfully:', response);
          this.successMessage = 'User logged in successfully.';
          this.setCookie (loginCredentials.email,true);
          setTimeout(() => {
            window.location.href = 'http://localhost:8080/ ';
          }, 2000); 
          
          
        },
        error: (error) => {
          console.error('Error:', error);
          this.errorMessage = error.error.message || 'An error occurred. Please try again.';
        },
      });
    } else {
      console.log('Form is invalid');
    }
  }
}
