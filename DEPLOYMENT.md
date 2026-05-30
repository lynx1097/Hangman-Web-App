# Deployment

Three apps, two targets:

| App | Path | Hosted on |
|-----|------|-----------|
| Angular client (`hg-front-game`) | `https://lynx1097.github.io/Hangman-Web-App/app/` | GitHub Pages |
| Vue game (`hangman-game`) | `https://lynx1097.github.io/Hangman-Web-App/game/` | GitHub Pages |
| Laravel API (`hg-backend`) | _TBD_ | External PHP host (e.g. InfinityFree) |

Both frontends share the origin `https://lynx1097.github.io`, so `localStorage['auth_token']` is shared. The Angular login also passes the token via `?token=` on redirect, so the handoff works cross-origin in local dev too.

---

## Frontends → GitHub Pages (automated)

The workflow [`.github/workflows/deploy-pages.yml`](.github/workflows/deploy-pages.yml) builds both SPAs on every push to `main` and deploys them to one Pages site (`/app` + `/game`).

### One-time GitHub setup
1. **Settings → Pages →** Source: **GitHub Actions**.
2. **Settings → Environments →** create **`github-pages`** and add these **Variables** (or Secrets — flip `vars.`→`secrets.` in the workflow):

   | Key | Value |
   |-----|-------|
   | `BACKEND_API_BASE` | `https://YOUR-BACKEND-DOMAIN/api` _(set once the backend host is chosen)_ |
   | `APP_BASE_HREF` | `/Hangman-Web-App/app/` |
   | `GAME_PUBLIC_PATH` | `/Hangman-Web-App/game/` |
   | `GAME_URL` | `https://lynx1097.github.io/Hangman-Web-App/game/` |
   | `LOGIN_URL` | `https://lynx1097.github.io/Hangman-Web-App/app/#/login` |

   The workflow falls back to these exact defaults if a value is unset, so it builds even before you create them — only `BACKEND_API_BASE` truly needs your real value.

> These values are compiled into the public JS bundles. They are **configuration, not secrets** — don't put anything confidential here.

### What the workflow does
- Regenerates `hg-front-game/src/environment/environment.prod.ts` from `BACKEND_API_BASE` + `GAME_URL`, then `ng build --configuration production --base-href $APP_BASE_HREF` → `_site/app`.
- Builds Vue with `VUE_APP_PUBLIC_PATH` / `VUE_APP_API_BASE` / `VUE_APP_LOGIN_URL` → `_site/game`.
- Adds `.nojekyll` and a root `index.html` redirect to `/app/`, then deploys.

Angular uses **hash routing** (`/app/#/login`) so deep links and refreshes work on Pages with no server rewrites.

---

## Backend → external PHP host (manual, host TBD)

App-level prod config is already in place; the host-specific bits (web server, build/release automation) are intentionally **deferred** until we pick a host.

### Production environment variables (set on the host; never commit `.env`)
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # php artisan key:generate --show
APP_URL=https://YOUR-BACKEND-DOMAIN

FRONTEND_ORIGINS=https://lynx1097.github.io   # CORS allow-list (config/cors.php)

DB_CONNECTION=mysql
DB_HOST=gateway01.<region>.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=hg_backend
DB_USERNAME=<prefix>.root
DB_PASSWORD=<password>
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt   # Linux CA bundle (TiDB TLS)

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Release commands (on the host)
```
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

The web root must point at `hg-backend/public`. The health check is `GET /up`.

---

## Open questions to explore (backend hosting)
- **InfinityFree (and similar free PHP hosts):** typically **no SSH/Composer and no Artisan** — you may need to upload a fully built app (with `vendor/`) over FTP and run migrations another way. They also frequently **block outbound connections to external databases**, which would prevent reaching **TiDB Cloud**. Verify both before committing to the host; otherwise consider a host that allows Composer + outbound DB (Render/Railway/Fly), or a DB the host provides.
- **HTTPS is mandatory:** GitHub Pages is HTTPS, so the backend must be HTTPS too or browsers will block the API calls as mixed content.

---

## Local development (unchanged)
```
# backend
cd hg-backend && php artisan serve            # http://127.0.0.1:8000

# angular
cd hg-front-game && npm start                 # http://localhost:4200  (hash routing: /#/login)

# vue
cd hangman-game && npm run serve              # http://localhost:8080
```
Log in on the Angular app → it stores the token and redirects to `http://localhost:8080/?token=…` → the Vue game starts authenticated.
