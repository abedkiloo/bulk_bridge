#!/bin/sh

# Fail fast
set -e

# Ensure dependencies are autoloadable
php artisan config:clear >/dev/null 2>&1 || true

# Run pending migrations (if any)
php artisan migrate --force || true

# Serve the app
exec php artisan serve --host=0.0.0.0 --port=8000


