import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { TOKEN_KEY } from './auth.interceptor';

/**
 * Blocks access to protected routes when there is no auth token,
 * redirecting to the login page.
 */
export const authGuard: CanActivateFn = () => {
  const router = inject(Router);
  if (localStorage.getItem(TOKEN_KEY)) {
    return true;
  }
  router.navigate(['/login']);
  return false;
};
