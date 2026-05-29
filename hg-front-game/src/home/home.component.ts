import { Component } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-home',
  standalone: true,
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.scss']
})
export class HomeComponent {
  
  constructor(private router: Router) {}
 /*  openGame() {
    window.location.href = 'http://localhost:8080/ ';
  } */
  navigateToLogin(){
    this.router.navigate(['/login']);
  }
  navigateToSignup(){
    this.router.navigate(['/signup']);
  }
  navigateToProfile() {
    this.router.navigate(['/profile']);
  }

}
