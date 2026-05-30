import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ApiService } from '../app/api.service';

@Component({
    selector: 'app-leaderboard',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './leaderboard.component.html',
    styleUrls: ['./leaderboard.component.scss'],
})
export class LeaderboardComponent {
    entries: any[] = [];
    loading = true;
    error = '';

    constructor(private api: ApiService, private router: Router) {}

    ngOnInit(): void {
        this.api.getLeaderboard().subscribe({
            next: (rows) => {
                this.entries = rows || [];
                this.loading = false;
            },
            error: () => {
                this.error = 'Could not load the leaderboard.';
                this.loading = false;
            },
        });
    }

    medal(index: number): string {
        return ['🥇', '🥈', '🥉'][index] ?? String(index + 1);
    }

    goHome(): void {
        this.router.navigate(['/']);
    }
}
