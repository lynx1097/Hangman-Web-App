import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../environment/environment';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private baseUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // Authentication
  register(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/auth/register`, data);
  }

  login(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/auth/login`, data);
  }

  logout(): Observable<any> {
    return this.http.post(`${this.baseUrl}/auth/logout`, {});
  }

  // User Management
  getUser(userId: string): Observable<any> {
    return this.http.get(`${this.baseUrl}/users/${userId}`);
  }

  updateUser(userId: string, data: any): Observable<any> {
    return this.http.put(`${this.baseUrl}/users/${userId}`, data);
  }

  deleteUser(userId: string): Observable<any> {
    return this.http.delete(`${this.baseUrl}/users/${userId}`);
  }

  // Games
  getGames(): Observable<any> {
    return this.http.get(`${this.baseUrl}/games`);
  }

  createGame(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/games`, data);
  }

  makeGuess(gameId: string, data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/games/${gameId}/guesses`, data);
  }

  // Leaderboard
  getLeaderboard(): Observable<any> {
    return this.http.get(`${this.baseUrl}/leaderboard`);
  }

  getUserLeaderboard(userId: string): Observable<any> {
    return this.http.get(`${this.baseUrl}/leaderboard/users/${userId}`);
  }
}
