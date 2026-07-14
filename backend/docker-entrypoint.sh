#!/bin/sh
set -e

# Wait for MySQL to be ready (healthcheck handles the hard wait, this is a soft retry)
until php artisan migrate --force 2>/dev/null; do
    echo "Waiting for database..."
    sleep 2
done

php artisan db:seed --force

exec "$@"
