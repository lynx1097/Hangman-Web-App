#!/usr/bin/env bash
set -e

# Render injects environment variables into the running container (not the build),
# so anything that needs DB credentials or APP_KEY must run here at start-up.

# Fail fast with a clear message if the app key is missing.
if [ -z "${APP_KEY}" ]; then
    echo "WARNING: APP_KEY is not set. Set it in the Render environment (php artisan key:generate --show)."
fi

# Make sure the database schema exists on whatever DB the env points to.
# --force is required to run migrations in production non-interactively.
echo "Running database migrations..."
php artisan migrate --force || echo "WARNING: migrations failed (check DB_* / MYSQL_ATTR_SSL_CA)."

# Cache config/routes now that the runtime env is available.
php artisan config:cache || true
php artisan route:cache || true

# Generate Swagger docs now that the full env is available.
echo "Generating Swagger docs..."
php artisan l5-swagger:generate || echo "WARNING: Swagger generation failed."

# Hand off to the image's default command (apache2-foreground).
exec "$@"
